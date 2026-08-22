<?php

namespace Modules\AfisPortal\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use League\CommonMark\GithubFlavoredMarkdownConverter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Modules\AdmmInventory\Models\Client;
use Modules\AfisPortal\Models\AfisGeneratedReport;
use Modules\AfisPortal\Services\ReportDataService;

class ReportController extends Controller
{
    public function __construct(private ReportDataService $reportData) {}

public function standardReport(Request $request)
{
    set_time_limit(0);
    ini_set('memory_limit', '2048M');
    ini_set('max_execution_time', '0');

    $client         = Client::findOrFail($request->clientId);
    $from           = Carbon::parse($request->from)->startOfDay();
    $to             = Carbon::parse($request->to)->endOfDay();
    $selectedGroups = $request->selectedGroups
        ? array_filter(array_map('intval', (array) $request->selectedGroups))
        : [];

    $trackerCount = \Modules\AfisPipeline\Models\AfisTracker::where('client_id', $client->id)
        ->when(!empty($selectedGroups), fn($q) => $q->whereIn('navixy_group_id', $selectedGroups))
        ->count();

$isConsolidated = $trackerCount > 200;
    if ($isConsolidated) {
        // Use consolidated report service
        $consolidatedService = app(\Modules\AfisPortal\Services\ConsolidatedReportService::class);
        $pdfContent = $consolidatedService->generate($client, $from, $to, $selectedGroups);
    } else {
        $data = $this->reportData->buildReportData($client, $from, $to, $selectedGroups);
        if (isset($data['error'])) return back()->withErrors(['report' => $data['error']]);

        $pdfContent = Pdf::loadView('afisportal::reports.standard', $data)
            ->setPaper('a4', 'landscape')
            ->setOptions(['defaultFont' => 'sans-serif', 'isHtml5ParserEnabled' => true, 'isRemoteEnabled' => false])
            ->output();
    }

    $filename = strtolower(str_replace(' ', '-', $client->name))
        . ($isConsolidated ? '-consolidated-' : '-standard-')
        . $from->format('Y-m-d') . '-to-' . $to->format('Y-m-d') . '.pdf';
    $filePath = 'reports/' . $filename;

    Storage::disk('local')->put($filePath, $pdfContent);

    AfisGeneratedReport::create([
        'client_id'    => $client->id,
        'generated_by' => Auth::id(),
        'report_type'  => 'standard',
        'from_date'    => $from->toDateString(),
        'to_date'      => $to->toDateString(),
        'filename'     => $filename,
        'file_path'    => $filePath,
        'file_size'    => strlen($pdfContent),
    ]);

    return response($pdfContent)
        ->header('Content-Type', 'application/pdf')
        ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
}

    public function aiReport(Request $request)
    {
        $client  = Client::findOrFail($request->clientId);
        $from    = Carbon::parse($request->from)->startOfDay();
        $to      = Carbon::parse($request->to)->endOfDay();
$selectedGroups = $request->selectedGroups
    ? array_filter(array_map('intval', (array) $request->selectedGroups))
    : [];

$data = $this->reportData->buildReportData($client, $from, $to, $selectedGroups);        if (isset($data['error'])) return back()->withErrors(['report' => $data['error']]);

        $promptBuilder = app(\Modules\AfisEngine\Services\Prompts\PromptBuilder::class);
        $engine        = app(\Modules\AfisEngine\Services\AfisEngineService::class);

        // Fixed (non-AI) sections computed first so the AI prompt can
        // reference the exact same dormant_count/active_count — one source
        // of truth, no risk of the two disagreeing.
        $fixed = $promptBuilder->fleetIntelligenceFixedSections($data);
        $data  = array_merge($data, $fixed);

        $prompt = $promptBuilder->fleetIntelligenceFromData($data);

        try {
            $aiResponse = $engine->analyze($prompt);
        } catch (\Throwable $e) {
            $aiResponse = 'AI analysis unavailable: ' . $e->getMessage();
        }

        $data['ai_analysis']      = $aiResponse;
        $data['ai_analysis_html'] = (new GithubFlavoredMarkdownConverter())->convert($aiResponse)->getContent();

        $pdf = Pdf::loadView('afisportal::reports.ai-report', $data)
            ->setPaper('a4', 'portrait')
            ->setOptions(['defaultFont' => 'sans-serif', 'isHtml5ParserEnabled' => true, 'isRemoteEnabled' => false]);

        $filename   = strtolower(str_replace(' ', '-', $client->name)) . '-ai-report-' . $from->format('Y-m-d') . '-to-' . $to->format('Y-m-d') . '.pdf';
        $filePath   = 'reports/' . $filename;
        $pdfContent = $pdf->output();

        Storage::disk('local')->put($filePath, $pdfContent);

        AfisGeneratedReport::create([
            'client_id'       => $client->id,
            'generated_by'    => Auth::id(),
            'report_type'     => 'ai',
            'from_date'       => $from->toDateString(),
            'to_date'         => $to->toDateString(),
            'filename'        => $filename,
            'file_path'       => $filePath,
            'file_size'       => strlen($pdfContent),
        ]);

        return response($pdfContent)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    public function download(int $id)
    {
        $report = AfisGeneratedReport::findOrFail($id);
        $path   = Storage::disk('local')->path($report->file_path);

        if (!file_exists($path)) {
            abort(404, 'Report file not found.');
        }

        return response()->download($path, $report->filename, ['Content-Type' => 'application/pdf']);
    }

    public function downloadVehicleReport(int $id)
    {
        $report = \Modules\AfisEngine\Models\AfisAiReport::findOrFail($id);

        abort_unless($report->report_path, 404);

        $path = Storage::disk('local')->path($report->report_path);
        abort_unless(file_exists($path), 404);

        $tracker = \Modules\AfisPipeline\Models\AfisTracker::find($report->tracker_id);
        $label   = $tracker?->label ?? 'vehicle';

        return response()->download($path, "Vehicle_Report_{$label}_{$report->id}.pdf", ['Content-Type' => 'application/pdf']);
    }

    public function destroy(int $id)
    {
        $report = AfisGeneratedReport::findOrFail($id);
        Storage::disk('local')->delete($report->file_path);
        $report->delete();

        return back()->with('success', 'Report deleted.');
    }

    public function clientStandardReport(Request $request)
    {
        $client = $this->resolveClientFromAuth();

        $from    = Carbon::parse($request->from ?? now()->startOfMonth())->startOfDay();
        $to      = Carbon::parse($request->to   ?? now()->endOfMonth())->endOfDay();
$selectedGroups = $request->selectedGroups
    ? array_filter(array_map('intval', (array) $request->selectedGroups))
    : [];

$data = $this->reportData->buildReportData($client, $from, $to, $selectedGroups);        if (isset($data['error'])) return back()->withErrors(['report' => $data['error']]);

        $pdf = Pdf::loadView('afisportal::reports.standard', $data)
            ->setPaper('a4', 'landscape')
            ->setOptions(['defaultFont' => 'sans-serif', 'isHtml5ParserEnabled' => true, 'isRemoteEnabled' => false]);

        $filename = strtolower(str_replace(' ', '-', $client->name))
            . '-standard-'
            . $from->format('Y-m-d') . '-to-' . $to->format('Y-m-d') . '.pdf';

        $filePath   = 'reports/' . $filename;
        $pdfContent = $pdf->output();

        Storage::disk('local')->put($filePath, $pdfContent);

        AfisGeneratedReport::create([
            'client_id'       => $client->id,
            'generated_by'    => Auth::id(),
            'report_type'     => 'standard',
            'from_date'       => $from->toDateString(),
            'to_date'         => $to->toDateString(),
            'filename'        => $filename,
            'file_path'       => $filePath,
            'file_size'       => strlen($pdfContent),
        ]);

        return response($pdfContent)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    public function clientAiReport(Request $request)
    {
        $client = $this->resolveClientFromAuth();

        $from    = Carbon::parse($request->from ?? now()->startOfMonth())->startOfDay();
        $to      = Carbon::parse($request->to   ?? now()->endOfMonth())->endOfDay();
$selectedGroups = $request->selectedGroups
    ? array_filter(array_map('intval', (array) $request->selectedGroups))
    : [];

$data = $this->reportData->buildReportData($client, $from, $to, $selectedGroups);        if (isset($data['error'])) return back()->withErrors(['report' => $data['error']]);

        $promptBuilder = app(\Modules\AfisEngine\Services\Prompts\PromptBuilder::class);
        $engine        = app(\Modules\AfisEngine\Services\AfisEngineService::class);

        // Fixed (non-AI) sections computed first so the AI prompt can
        // reference the exact same dormant_count/active_count — one source
        // of truth, no risk of the two disagreeing.
        $fixed = $promptBuilder->fleetIntelligenceFixedSections($data);
        $data  = array_merge($data, $fixed);

        $prompt = $promptBuilder->fleetIntelligenceFromData($data);

        try {
            $aiResponse = $engine->analyze($prompt);
        } catch (\Throwable $e) {
            $aiResponse = 'AI analysis unavailable: ' . $e->getMessage();
        }

        $data['ai_analysis']      = $aiResponse;
        $data['ai_analysis_html'] = (new GithubFlavoredMarkdownConverter())->convert($aiResponse)->getContent();

        $pdf = Pdf::loadView('afisportal::reports.ai-report', $data)
            ->setPaper('a4', 'portrait')
            ->setOptions(['defaultFont' => 'sans-serif', 'isHtml5ParserEnabled' => true, 'isRemoteEnabled' => false]);

        $filename = strtolower(str_replace(' ', '-', $client->name))
            . '-ai-report-'
            . $from->format('Y-m-d') . '-to-' . $to->format('Y-m-d') . '.pdf';

        $filePath   = 'reports/' . $filename;
        $pdfContent = $pdf->output();

        Storage::disk('local')->put($filePath, $pdfContent);

        AfisGeneratedReport::create([
            'client_id'       => $client->id,
            'generated_by'    => Auth::id(),
            'report_type'     => 'ai',
            'from_date'       => $from->toDateString(),
            'to_date'         => $to->toDateString(),
            'filename'        => $filename,
            'file_path'       => $filePath,
            'file_size'       => strlen($pdfContent),
        ]);

        return response($pdfContent)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    private function resolveClientFromAuth(): \Modules\AdmmInventory\Models\Client
{
    $user = Auth::user();

    // Method 1: Direct client_id (independent account users)
    if ($user->client_id) {
        $client = \Modules\AdmmInventory\Models\Client::find($user->client_id);
        if ($client) return $client;
    }

    if (!$user->navixy_security_group_id || !$user->navixy_instance) {
        abort(403, 'Your account is not linked to a client fleet.');
    }

    // Method 2: Match by security_group_id (master account sub-users)
    $client = \Modules\AdmmInventory\Models\Client::where('navixy_security_group_id', $user->navixy_security_group_id)
        ->where('navixy_instance', $user->navixy_instance)
        ->first();

    if ($client) return $client;

    // Method 3: Extended security groups (ZETDC multi-group)
    $extended = \Modules\AdmmInventory\Models\ClientSecurityGroup::where('navixy_security_group_id', $user->navixy_security_group_id)
        ->where('navixy_instance', $user->navixy_instance)
        ->first();

    if ($extended) return $extended->client;

    abort(403, 'Your account is not linked to a client fleet.');
}
}