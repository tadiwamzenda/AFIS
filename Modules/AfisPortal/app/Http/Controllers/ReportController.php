<?php

namespace Modules\AfisPortal\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
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
        $client  = Client::findOrFail($request->clientId);
        $from    = Carbon::parse($request->from)->startOfDay();
        $to      = Carbon::parse($request->to)->endOfDay();
        $groupId = $request->groupId ?: null;

        $data = $this->reportData->buildReportData($client, $from, $to, $groupId);
        if (isset($data['error'])) return back()->withErrors(['report' => $data['error']]);

        $pdf = Pdf::loadView('afisportal::reports.standard', $data)
            ->setPaper('a4', 'landscape')
            ->setOptions(['defaultFont' => 'sans-serif', 'isHtml5ParserEnabled' => true, 'isRemoteEnabled' => false]);

        $filename  = strtolower(str_replace(' ', '-', $client->name)) . '-standard-' . $from->format('Y-m-d') . '-to-' . $to->format('Y-m-d') . '.pdf';
        $filePath  = 'reports/' . $filename;
        $pdfContent = $pdf->output();

        Storage::disk('local')->put($filePath, $pdfContent);

        AfisGeneratedReport::create([
            'client_id'       => $client->id,
            'generated_by'    => Auth::id(),
            'report_type'     => 'standard',
            'from_date'       => $from->toDateString(),
            'to_date'         => $to->toDateString(),
            'navixy_group_id' => $groupId,
            'filename'        => $filename,
            'file_path'       => $filePath,
            'file_size'       => strlen($pdfContent),
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
        $groupId = $request->groupId ?: null;

        $data = $this->reportData->buildReportData($client, $from, $to, $groupId);
        if (isset($data['error'])) return back()->withErrors(['report' => $data['error']]);

        $promptBuilder  = app(\Modules\AfisEngine\Services\Prompts\PromptBuilder::class);
        $engine         = app(\Modules\AfisEngine\Services\AfisEngineService::class);
        $prompt         = $promptBuilder->fleetIntelligenceFromData($data);

        try {
            $aiResponse = $engine->analyze($prompt);
        } catch (\Throwable $e) {
            $aiResponse = 'AI analysis unavailable: ' . $e->getMessage();
        }

        $data['ai_analysis'] = $aiResponse;

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
            'navixy_group_id' => $groupId,
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
        $groupId = $request->groupId ?: null;

        $data = $this->reportData->buildReportData($client, $from, $to, $groupId);
        if (isset($data['error'])) return back()->withErrors(['report' => $data['error']]);

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
            'navixy_group_id' => $groupId,
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
        $groupId = $request->groupId ?: null;

        $data = $this->reportData->buildReportData($client, $from, $to, $groupId);
        if (isset($data['error'])) return back()->withErrors(['report' => $data['error']]);

        $promptBuilder = app(\Modules\AfisEngine\Services\Prompts\PromptBuilder::class);
        $engine        = app(\Modules\AfisEngine\Services\AfisEngineService::class);
        $prompt        = $promptBuilder->fleetIntelligenceFromData($data);

        try {
            $aiResponse = $engine->analyze($prompt);
        } catch (\Throwable $e) {
            $aiResponse = 'AI analysis unavailable: ' . $e->getMessage();
        }

        $data['ai_analysis'] = $aiResponse;

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
            'navixy_group_id' => $groupId,
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

        if (!$user->navixy_security_group_id || !$user->navixy_instance) {
            abort(403, 'Your account is not linked to a client fleet.');
        }

        $client = \Modules\AdmmInventory\Models\Client::where('navixy_security_group_id', $user->navixy_security_group_id)
            ->where('navixy_instance', $user->navixy_instance)
            ->first();

        if ($client) return $client;

        $extended = \Modules\AdmmInventory\Models\ClientSecurityGroup::where('navixy_security_group_id', $user->navixy_security_group_id)
            ->where('navixy_instance', $user->navixy_instance)
            ->first();

        if ($extended) return $extended->client;

        abort(403, 'Your account is not linked to a client fleet.');
    }
}