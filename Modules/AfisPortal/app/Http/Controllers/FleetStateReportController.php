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
use Modules\AfisPortal\Services\FleetStateCounter;
use Modules\AfisPortal\Services\OfflineReportPdfBuilder;
use Barryvdh\DomPDF\Facade\Pdf;

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
        $title     = trim("{$titleBase} States Report {$data['periodLabel']}" . ($durationSuffix ? " {$durationSuffix}" : ''));

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
            'action_party_filter' => $data['actionPartyFilter'] ?: null,
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
        [$from, $to, $periodLabel] = $this->resolvePeriod($request);

        $stateFilter       = $request->filter   ?? 'all';
        $durationFilter    = $request->duration ?? '';
        $commentFilter     = $request->comment  ?? '';
        $actionPartyFilter = $request->action_party ?? '';
        $search            = $request->search   ?? '';

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

        $userNames = \App\Models\User::whereIn('id', $incidentRecords->pluck('triage_updated_by')->filter()->unique())->pluck('name', 'id');

        $incidents = $incidentRecords->map(function ($incident) use ($groupCache, $now, $userNames) {
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
                // blank counts as the defaults (same rule as the dashboard panels)
                'comment'      => $incident->comment ?: AfisOfflineIncident::DEFAULT_COMMENT,
                'action_party' => $incident->action_party ?: AfisOfflineIncident::DEFAULT_PARTY,
                'updated_at'   => $incident->triage_updated_at?->timezone('Africa/Harare')->format('d M Y H:i'),
                'updated_by'   => $incident->triage_updated_by ? ($userNames[$incident->triage_updated_by] ?? null) : null,
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

        if ($actionPartyFilter !== '') {
            $incidents = $incidents->filter(fn($i) => $i['action_party'] === $actionPartyFilter);
        }

        $incidents = $incidents->sortByDesc('duration_sec')->values();

        $totalIncidents = $incidents->count();
        $stillOffline   = $incidents->where('is_open', true)->count();
        $countedOffline = app(FleetStateCounter::class)->forClients([$clientId])['offline'];
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
            'stateFilter', 'durationFilter', 'commentFilter', 'actionPartyFilter', 'search',
            'fleetSize', 'groupBreakdown', 'periodLabel', 'countedOffline'
        );
    }

    /**
     * Period for a report, in Harare time: daily (today), weekly (Mon-Sun),
     * monthly (this month) or custom (from/to). With no period the old
     * behaviour applies (from/to if given, otherwise the last 30 days).
     *
     * @return array{0:Carbon,1:Carbon,2:string} from, to, label
     */
    private function resolvePeriod(Request $request): array
    {
        $tz  = 'Africa/Harare';
        $now = Carbon::now($tz);

        switch ($request->period) {
            case 'daily':
                return [$now->copy()->startOfDay(), $now->copy()->endOfDay(), 'Daily'];
            case 'weekly':
                return [$now->copy()->startOfWeek(Carbon::MONDAY), $now->copy()->endOfWeek(Carbon::SUNDAY), 'Weekly'];
            case 'monthly':
                return [$now->copy()->startOfMonth(), $now->copy()->endOfMonth(), 'Monthly'];
            case 'yearly':
                return [$now->copy()->startOfYear(), $now->copy()->endOfYear(), 'Yearly'];
        }

        try {
            $from = Carbon::parse($request->from ?? $now->copy()->subDays(30), $tz)->startOfDay();
            $to   = Carbon::parse($request->to   ?? $now, $tz)->endOfDay();
        } catch (\Throwable) {
            $from = $now->copy()->subDays(30)->startOfDay();
            $to   = $now->copy()->endOfDay();
        }
        if ($from->gt($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        return [$from, $to, $request->period === 'custom' ? 'Custom' : 'Period'];
    }

    // ─── All-clients report (Daily / Weekly / Monthly / Yearly) ──────────────

    public function overviewReport(Request $request)
    {
        $period = in_array($request->period, ['daily', 'weekly', 'monthly', 'yearly'], true) ? $request->period : 'monthly';
        $request->merge(['period' => $period]);
        [$from, $to, $periodLabel] = $this->resolvePeriod($request);

        $clients   = Client::active()->orderBy('name')->get(['id', 'name']);
        $clientIds = $clients->pluck('id')->all();

        $counter  = app(FleetStateCounter::class);
        $totals   = $counter->forClients($clientIds);
        $byClient = $counter->byClient($clientIds);

        // Incidents overlapping the period, per client
        $inRange = AfisOfflineIncident::whereHas('tracker', fn($q) => $q->whereIn('client_id', $clientIds))
            ->with('tracker:id,client_id')
            ->where('went_offline_at', '<=', $to)
            ->where(fn($q) => $q->whereNull('came_online_at')->orWhere('came_online_at', '>=', $from))
            ->get();

        $now = Carbon::now('Africa/Harare');
        $perClient = $inRange->groupBy(fn($i) => $i->tracker?->client_id)->map(function ($rows) use ($now) {
            $durations = $rows->map(function ($i) use ($now) {
                $start = $this->harareTime($i, 'went_offline_at');
                $end   = $i->came_online_at ? $this->harareTime($i, 'came_online_at') : $now;
                return abs($start->diffInSeconds($end));
            });
            return ['incidents' => $rows->count(), 'avg' => $durations->avg(), 'longest' => $durations->max()];
        });

        $rows = $clients->map(function ($cl) use ($byClient, $perClient) {
            $c = $byClient[$cl->id];
            $p = $perClient[$cl->id] ?? null;
            return [
                'client'    => $cl->name,
                'total'     => $c['total'],
                'online'    => $c['online'],
                'offline'   => $c['offline'],
                'excluded'  => $c['excluded'],
                'incidents' => $p['incidents'] ?? 0,
                'avg'       => $p ? $this->formatDuration((int) $p['avg']) : '—',
                'longest'   => $p ? $this->formatDuration((int) $p['longest']) : '—',
            ];
        })->filter(fn($r) => $r['total'] > 0)->values();

        // Vehicles offline right now (all of them), counted ones first
        $groupCache = AfisTrackerGroup::pluck('title', 'navixy_group_id');
        $clientName = $clients->pluck('name', 'id');
        $offlineNow = AfisTracker::whereIn('client_id', $clientIds)->where('online_status', 'offline')->get();
        $open       = AfisOfflineIncident::open()->whereIn('tracker_id', $offlineNow->pluck('id'))->get()->keyBy('tracker_id');
        $userNames  = \App\Models\User::whereIn('id', $open->pluck('triage_updated_by')->filter()->unique())->pluck('name', 'id');

        $vehicles = $offlineNow->map(function ($t) use ($open, $groupCache, $clientName, $now, $userNames) {
            $inc   = $open->get($t->id);
            $since = $inc ? $this->harareTime($inc, 'went_offline_at') : null;
            $sec   = $since ? abs($now->diffInSeconds($since)) : 0;
            $comment = $inc?->comment ?: AfisOfflineIncident::DEFAULT_COMMENT;
            $party   = $inc?->action_party ?: AfisOfflineIncident::DEFAULT_PARTY;

            return [
                'client'       => $clientName[$t->client_id] ?? '—',
                'label'        => $t->label,
                'group'        => $groupCache[$t->navixy_group_id] ?? '—',
                'since'        => $since?->format('d M Y H:i') ?? '—',
                'duration'     => $since ? $this->formatDuration($sec) : '—',
                'duration_sec' => $sec,
                'comment'      => $comment,
                'action_party' => $party,
                'updated_at'   => $inc?->triage_updated_at?->timezone('Africa/Harare')->format('d M Y H:i'),
                'counted'      => $comment === AfisOfflineIncident::DEFAULT_COMMENT && $party === AfisOfflineIncident::DEFAULT_PARTY,
            ];
        })->sort(fn($a, $b) => [$b['counted'], $b['duration_sec']] <=> [$a['counted'], $a['duration_sec']])->values();

        $data = [
            'from' => $from, 'to' => $to, 'periodLabel' => $periodLabel,
            'totals' => $totals, 'rows' => $rows, 'vehicles' => $vehicles,
            'incidentsTotal' => $inRange->count(), 'isPdf' => true,
        ];

        return Pdf::loadView('afisportal::reports.offline-overview-report', $data)
            ->setPaper('a4', 'landscape')
            ->download('Fleet_States_Report_' . $periodLabel . '_' . now('Africa/Harare')->format('Ymd_His') . '.pdf');
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