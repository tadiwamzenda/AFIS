<?php

namespace Modules\AfisPortal\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Modules\AdmmInventory\Models\Client;
use Modules\AfisPipeline\Models\AfisTracker;
use Modules\AfisPipeline\Models\AfisTrackerGroup;
use Modules\AfisPipeline\Models\AfisOfflineIncident;
use Modules\AfisPipeline\Models\AfisOfflineReportFile;
use Modules\AfisPortal\Services\OfflineReportPdfBuilder;

class FleetStateReportController extends Controller
{
    public function offlineReport(Request $request, int $clientId)
    {
        $data = $this->prepareReportData($request, $clientId);
        $data['isPdf'] = false;
        return view('afisportal::reports.offline-report', $data);
    }

    public function generateAndSave(Request $request, int $clientId)
    {
        $data   = $this->prepareReportData($request, $clientId);
        $client = $data['client'];

        $matchedTracker = null;
        if ($data['search']) {
            $matches = AfisTracker::where('client_id', $clientId)
                ->where('label', 'like', "%{$data['search']}%")
                ->get();
            if ($matches->count() === 1) {
                $matchedTracker = $matches->first();
            }
        }

        $durationSuffix = match ($data['durationFilter']) {
            'hourly' => '1-24h',
            'daily'  => '1-7d',
            'weekly' => '7d+',
            default  => '',
        };

        $titleBase = $matchedTracker ? $matchedTracker->label : $client->name;
        $title     = trim("{$titleBase} States Report" . ($durationSuffix ? " {$durationSuffix}" : ''));

        $data['isPdf'] = true;
        $pdfPath = app(OfflineReportPdfBuilder::class)->build($data, $titleBase);

        AfisOfflineReportFile::create([
            'client_id'       => $clientId,
            'tracker_id'      => $matchedTracker?->id,
            'title'           => $title,
            'file_path'       => $pdfPath,
            'state_filter'    => $data['stateFilter'],
            'duration_filter' => $data['durationFilter'] ?: null,
            'comment_filter'  => $data['commentFilter'] ?: null,
            'search_term'     => $data['search'] ?: null,
            'generated_by'    => Auth::id(),
        ]);

        return response()->download(Storage::disk('local')->path($pdfPath), $title . '.pdf');
    }

    public function downloadSaved(int $id)
    {
        $report = AfisOfflineReportFile::findOrFail($id);
        $path   = Storage::disk('local')->path($report->file_path);
        abort_unless(file_exists($path), 404);

        return response()->download($path, $report->title . '.pdf');
    }

    private function prepareReportData(Request $request, int $clientId): array
    {
        $client = Client::findOrFail($clientId);
        $from   = Carbon::parse($request->from ?? now()->subDays(30))->startOfDay();
        $to     = Carbon::parse($request->to   ?? now())->endOfDay();

        $stateFilter    = $request->filter   ?? 'all';
        $durationFilter = $request->duration ?? '';
        $commentFilter  = $request->comment  ?? '';
        $search         = $request->search   ?? '';

        $incidentRecords = AfisOfflineIncident::whereHas('tracker', function ($q) use ($clientId, $search) {
                $q->where('client_id', $clientId);
                if ($search) {
                    $q->where('label', 'like', "%{$search}%");
                }
            })
            ->with('tracker')
            ->where('went_offline_at', '<=', $to)
            ->where(function ($q) use ($from) {
                $q->whereNull('came_online_at')->orWhere('came_online_at', '>=', $from);
            })
            ->get();

        $groupCache = AfisTrackerGroup::pluck('title', 'navixy_group_id');
        $now        = Carbon::now('Africa/Harare');

        $incidents = $incidentRecords->map(function ($incident) use ($groupCache, $now) {
            $tracker     = $incident->tracker;
            $wentOffline = $this->harareTime($incident, 'went_offline_at');
            $cameOnline  = $incident->came_online_at ? $this->harareTime($incident, 'came_online_at') : null;
            $duration    = abs($wentOffline->diffInSeconds($cameOnline ?? $now));

            return [
                'label'        => $tracker?->label ?? '—',
                'group'        => $groupCache[$tracker?->navixy_group_id] ?? '—',
                'went_offline' => $wentOffline->format('d M Y H:i'),
                'came_online'  => $cameOnline ? $cameOnline->format('d M Y H:i') : 'Still offline',
                'duration_sec' => $duration,
                'duration'     => $this->formatDuration($duration),
                'severity'     => $this->severity($duration),
                'bucket'       => $this->durationBucket($duration),
                'is_open'      => $cameOnline === null,
                'comment'      => $incident->comment,
                'resolution'   => $incident->resolution,
            ];
        });

        if ($stateFilter === 'offline') {
            $incidents = $incidents->filter(fn($i) => $i['is_open']);
        } elseif ($stateFilter === 'online') {
            $incidents = $incidents->filter(fn($i) => !$i['is_open']);
        }

        if ($durationFilter !== '') {
            $incidents = $incidents->filter(fn($i) => $i['bucket'] === $durationFilter);
        }

        if ($commentFilter !== '') {
            $incidents = $incidents->filter(fn($i) => $i['comment'] === $commentFilter);
        }

        $incidents = $incidents->sortByDesc('duration_sec')->values();

        $totalIncidents = $incidents->count();
        $stillOffline   = $incidents->where('is_open', true)->count();
        $avgDuration    = $totalIncidents > 0 ? $this->formatDuration((int) $incidents->avg('duration_sec')) : '—';
        $longestOffline = $totalIncidents > 0 ? $this->formatDuration((int) $incidents->max('duration_sec')) : '—';

        // Whole-fleet number + per-group breakdown, for the first-page summary
        $fleetSize      = AfisTracker::where('client_id', $clientId)->count();
        $isConsolidated = $fleetSize > 200; // same threshold as Standard/Consolidated Report

        $groupKey = fn($rows) => $isConsolidated
            ? $this->getParentRegion($rows['group'])
            : $rows['group'];

        $groupBreakdown = $incidents->groupBy($groupKey)->map(function ($rows, $groupName) {
            return [
                'group'           => $groupName,
                'total_incidents' => $rows->count(),
                'still_offline'   => $rows->where('is_open', true)->count(),
                'avg_duration'    => $rows->count() > 0 ? $this->formatDuration((int) $rows->avg('duration_sec')) : '—',
            ];
        })->sortByDesc('total_incidents')->values();

        return compact(
            'client', 'from', 'to', 'incidents',
            'totalIncidents', 'stillOffline', 'avgDuration', 'longestOffline',
            'stateFilter', 'durationFilter', 'commentFilter', 'search',
            'fleetSize', 'groupBreakdown'
        );
    }

      /**
     * See FleetStateDashboard::harareTime() for the full explanation —
     * same bug, same fix, applied here for report accuracy.
     */
    private function harareTime($model, string $attribute): ?Carbon
    {
        $raw = $model->getRawOriginal($attribute);
        return $raw ? Carbon::parse($raw, 'Africa/Harare') : null;
    }

    private function formatDuration(int $seconds): string
    {
        if ($seconds < 60)    return "{$seconds}s";
        if ($seconds < 3600)  return floor($seconds / 60) . 'm';
        if ($seconds < 86400) return floor($seconds / 3600) . 'h ' . floor(($seconds % 3600) / 60) . 'm';
        $days  = floor($seconds / 86400);
        $hours = floor(($seconds % 86400) / 3600);
        return "{$days}d {$hours}h";
    }

    private function severity(int $seconds): string
    {
        if ($seconds < 3600)  return 'low';
        if ($seconds < 86400) return 'medium';
        return 'high';
    }

    private function durationBucket(int $seconds): string
    {
        if ($seconds < 3600)   return 'none';
        if ($seconds < 86400)  return 'hourly';
        if ($seconds < 604800) return 'daily';
        return 'weekly';
    }

    // Same logic as ReportGenerator::getParentRegion() — kept identical so
    // ZETDC's region grouping is consistent everywhere it appears in AFIS.
    private function getParentRegion(string $title): string
    {
        $parts = explode(' ', trim($title));
        // Special case: ZETDC TR → use 3 words (ZETDC TR EAST / ZETDC TR WEST)
        if (count($parts) >= 3 && strtoupper($parts[0]) === 'ZETDC' && strtoupper($parts[1]) === 'TR') {
            return implode(' ', array_slice($parts, 0, 3));
        }
        return implode(' ', array_slice($parts, 0, 2));
    }
}