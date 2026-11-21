<?php

namespace Modules\AfisPortal\Livewire;

use Carbon\Carbon;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Modules\AfisPipeline\Models\AfisTracker;
use Modules\AfisPipeline\Models\AfisTrackerGroup;
use Modules\AfisPipeline\Models\AfisOfflineIncident;
use Modules\AfisPipeline\Models\AfisOfflineReportFile;
use Modules\AfisPortal\Services\FleetStateCounter;
use Modules\AuditLog\Models\AuditLog;

class FleetStateDashboard extends Component
{
    public int    $clientId;
    public string $view              = 'states'; // 'states' | 'reports'
    public string $filter            = 'all';
    public string $durationFilter    = '';
    public string $commentFilter     = '';
    public string $actionPartyFilter = '';
    public string $search            = '';
    public string $reportSearch      = '';
    public array  $comments          = [];   // incident id => comment
    public array  $actionParties     = [];   // incident id => action party
    public array  $pendingComments   = [];   // tracker id => comment, for offline vehicles with no incident yet
    public array  $pendingParties    = [];   // tracker id => action party

    // Offline Report period (Daily / Weekly / Monthly / Custom)
    public string $reportPeriod = 'monthly';
    public string $reportFrom   = '';
    public string $reportTo     = '';

    public function mount(int $clientId): void
    {
        $this->clientId = $clientId;

        if (request('tab') === 'reports') {
            $this->view = 'reports';
        }

        $now = Carbon::now('Africa/Harare');
        $this->reportTo   = $now->toDateString();
        $this->reportFrom = $now->copy()->subDays(6)->toDateString();

        $openIncidents = AfisOfflineIncident::whereHas('tracker', fn($q) => $q->where('client_id', $clientId))
            ->open()
            ->get();

        foreach ($openIncidents as $incident) {
            $this->comments[$incident->id]     = $incident->comment ?? '';
            $this->actionParties[$incident->id] = $incident->action_party ?? '';
        }
    }

    public function setView(string $view): void
    {
        $this->view = $view;
    }

    public function setFilter(string $filter): void
    {
        $this->filter = $filter;
    }

    public function setDurationFilter(string $bucket): void
    {
        $this->durationFilter = $bucket;
        if ($bucket !== '') {
            $this->filter = 'offline';
        }
    }

    public function setCommentFilter(string $comment): void
    {
        $this->commentFilter = $comment;
        if ($comment !== '') {
            $this->filter = 'offline';
        }
    }

    public function setActionPartyFilter(string $party): void
    {
        $this->actionPartyFilter = $party;
        if ($party !== '') {
            $this->filter = 'offline';
        }
    }

    private function durationBucket(int $seconds): string
    {
        if ($seconds < 3600)   return 'none';
        if ($seconds < 86400)  return 'hourly';
        if ($seconds < 604800) return 'daily';
        return 'weekly';
    }

    /**
     * Save Comment and Action Party for one incident. Returns true when something changed.
     * The "last changed" time and user are stamped only when Comment or Action Party changed.
     */
    private function persist(int $incidentId, ?string $comment, ?string $party): bool
    {
        $incident = AfisOfflineIncident::whereKey($incidentId)
            ->whereHas('tracker', fn($q) => $q->where('client_id', $this->clientId))
            ->first();
        if (!$incident) {
            return false;
        }

        $comment = $comment ?: null;
        $party   = $party ?: null;

        if (($comment !== null && !in_array($comment, AfisOfflineIncident::COMMENTS, true))
            || ($party !== null && !in_array($party, AfisOfflineIncident::ACTION_PARTIES, true))) {
            return false;
        }

        if ($comment === $incident->comment && $party === $incident->action_party) {
            return false;
        }

        $before = ['comment' => $incident->comment, 'action_party' => $incident->action_party];

        $incident->update([
            'comment'           => $comment,
            'action_party'      => $party,
            'triage_updated_at' => now(),
            'triage_updated_by' => Auth::id(),
        ]);

        AuditLog::create([
            'event'       => 'offline_incident.updated',
            'module'      => 'AfisPipeline',
            'entity_type' => AfisOfflineIncident::class,
            'entity_id'   => $incident->id,
            'user_id'     => Auth::id(),
            'ip_address'  => request()->ip(),
            'user_agent'  => request()->userAgent(),
            'created_at'  => now(),
            'data'        => [
                'tracker_id'    => $incident->tracker_id,
                'tracker_label' => $incident->tracker?->label,
                'before'        => $before,
                'after'         => ['comment' => $comment, 'action_party' => $party],
            ],
        ]);

        return true;
    }

    /**
     * An offline vehicle with no incident record yet (shown as "Pending"): create its
     * open incident with the chosen Comment / Action Party. If the alert check created
     * one in the meantime, update that instead.
     */
    private function persistPending(int $trackerId, ?string $comment, ?string $party): bool
    {
        $comment = $comment ?: null;
        $party   = $party ?: null;

        if ($comment === null && $party === null) {
            return false;
        }
        if (($comment !== null && !in_array($comment, AfisOfflineIncident::COMMENTS, true))
            || ($party !== null && !in_array($party, AfisOfflineIncident::ACTION_PARTIES, true))) {
            return false;
        }

        $tracker = AfisTracker::where('client_id', $this->clientId)->where('online_status', 'offline')->find($trackerId);
        if (!$tracker) {
            return false;
        }

        $existing = AfisOfflineIncident::open()->where('tracker_id', $trackerId)->first();
        if ($existing) {
            $this->comments[$existing->id]      = $comment ?? '';
            $this->actionParties[$existing->id] = $party ?? '';
            return $this->persist($existing->id, $comment, $party);
        }

        // offline since: when the tracker went offline, else its last sync, else now (Harare wall-clock, as the pipeline stores it)
        $since = $this->harareTime($tracker, 'online_status_changed_at')
              ?? $this->harareTime($tracker, 'last_synced_at')
              ?? Carbon::now('Africa/Harare');

        $incident = AfisOfflineIncident::create([
            'tracker_id'        => $trackerId,
            'client_id'         => $this->clientId,
            'went_offline_at'   => $since,
            'comment'           => $comment,
            'action_party'      => $party,
            'triage_updated_at' => now(),
            'triage_updated_by' => Auth::id(),
        ]);

        $this->comments[$incident->id]      = $comment ?? '';
        $this->actionParties[$incident->id] = $party ?? '';
        unset($this->pendingComments[$trackerId], $this->pendingParties[$trackerId]);

        AuditLog::create([
            'event'       => 'offline_incident.created_manually',
            'module'      => 'AfisPipeline',
            'entity_type' => AfisOfflineIncident::class,
            'entity_id'   => $incident->id,
            'user_id'     => Auth::id(),
            'ip_address'  => request()->ip(),
            'user_agent'  => request()->userAgent(),
            'created_at'  => now(),
            'data'        => [
                'tracker_id'    => $trackerId,
                'tracker_label' => $tracker->label,
                'after'         => ['comment' => $comment, 'action_party' => $party],
            ],
        ]);

        return true;
    }

    /** Row Save button for a vehicle that has no incident yet. */
    public function savePending(int $trackerId): void
    {
        $changed = $this->persistPending(
            $trackerId,
            $this->pendingComments[$trackerId] ?? null,
            $this->pendingParties[$trackerId] ?? null
        );

        session()->flash('success', $changed ? 'Vehicle updated.' : 'Choose a Comment or Action Party first.');
    }

    /** Update button: save every vehicle on the page. */
    public function saveUpdates(): void
    {
        $changed = 0;

        foreach ($this->pendingComments + $this->pendingParties as $trackerId => $_) {
            if ($this->persistPending((int) $trackerId, $this->pendingComments[$trackerId] ?? null, $this->pendingParties[$trackerId] ?? null)) {
                $changed++;
            }
        }

        foreach ($this->comments as $incidentId => $comment) {
            if ($this->persist((int) $incidentId, $comment, $this->actionParties[$incidentId] ?? null)) {
                $changed++;
            }
        }

        session()->flash('success', $changed > 0
            ? "{$changed} vehicle record(s) updated."
            : 'No changes to save.');
    }

    /** Row Save button: save just this vehicle. */
    public function saveVehicle(int $incidentId): void
    {
        $changed = $this->persist(
            $incidentId,
            $this->comments[$incidentId] ?? null,
            $this->actionParties[$incidentId] ?? null
        );

        session()->flash('success', $changed ? 'Vehicle updated.' : 'No changes to save for this vehicle.');
    }

    public function deleteReport(int $id): void
    {
        $report = AfisOfflineReportFile::findOrFail($id);
        Storage::disk('local')->delete($report->file_path);
        $report->delete();
        session()->flash('success', 'Report deleted.');
    }

    private function buildVehicleStates(): array
    {
        $trackers = AfisTracker::where('client_id', $this->clientId)
            ->when($this->search, fn($q) => $q->where('label', 'like', "%{$this->search}%"))
            ->orderBy('label')
            ->get();

        if ($trackers->isEmpty()) return [];

        $trackerIds = $trackers->pluck('id')->toArray();

        $openIncidents = AfisOfflineIncident::whereIn('tracker_id', $trackerIds)
            ->open()
            ->get()
            ->keyBy('tracker_id');

        // Incidents created after this page loaded: show their stored values, not blanks
        foreach ($openIncidents as $inc) {
            if (!array_key_exists($inc->id, $this->comments)) {
                $this->comments[$inc->id]      = $inc->comment ?? '';
                $this->actionParties[$inc->id] = $inc->action_party ?? '';
            }
        }

        $userNames = \App\Models\User::whereIn('id', $openIncidents->pluck('triage_updated_by')->filter()->unique())
            ->pluck('name', 'id');

        $groupIds = $trackers->pluck('navixy_group_id')->filter()->unique()->toArray();
        $groups   = AfisTrackerGroup::whereIn('navixy_group_id', $groupIds)
            ->pluck('title', 'navixy_group_id');

        $now      = Carbon::now('Africa/Harare');
        $vehicles = [];

        foreach ($trackers as $tracker) {
            $status   = match ($tracker->online_status) {
                'online'  => 'online',
                'offline' => 'offline',
                default   => 'unknown',
            };
            $incident = $openIncidents->get($tracker->id);
            $since    = null;
            $duration = '—';
            $seconds  = 0;
            $bucket   = 'none';

            if ($status === 'offline' && $incident) {
                $since    = $this->harareTime($incident, 'went_offline_at');
                $seconds  = abs($now->diffInSeconds($since));
                $duration = $this->formatDuration($seconds);
                $bucket   = $this->durationBucket($seconds);
            } elseif ($tracker->last_synced_at) {
                $since    = $this->harareTime($tracker, 'last_synced_at');
                $seconds  = abs($now->diffInSeconds($since));
                $duration = $this->formatDuration($seconds);
            }

            $vehicles[] = [
                'id'          => $tracker->id,
                'label'       => $tracker->label,
                'group'       => $groups[$tracker->navixy_group_id] ?? '—',
                'status'      => $status,
                'since'       => $since?->format('d M Y H:i'),
                'duration'    => $duration,
                'seconds'     => $seconds,
                'bucket'      => $bucket,
                'incident_id' => $incident?->id,
                // blank counts as the defaults, so un-triaged vehicles stay in the Offline count
                'comment'     => $incident?->comment ?: AfisOfflineIncident::DEFAULT_COMMENT,
                'party'       => $incident?->action_party ?: AfisOfflineIncident::DEFAULT_PARTY,
                'updated_at'  => $incident?->triage_updated_at?->timezone('Africa/Harare')->format('d M Y H:i'),
                'updated_by'  => $incident?->triage_updated_by ? ($userNames[$incident->triage_updated_by] ?? null) : null,
            ];
        }

        if ($this->filter !== 'all') {
            $vehicles = array_values(array_filter($vehicles, fn($v) => $v['status'] === $this->filter));
        }

        if ($this->durationFilter !== '') {
            $vehicles = array_values(array_filter($vehicles, fn($v) => $v['bucket'] === $this->durationFilter));
        }

        if ($this->commentFilter !== '') {
            $vehicles = array_values(array_filter($vehicles, fn($v) => $v['status'] === 'offline' && $v['comment'] === $this->commentFilter));
        }

        if ($this->actionPartyFilter !== '') {
            $vehicles = array_values(array_filter($vehicles, fn($v) => $v['status'] === 'offline' && $v['party'] === $this->actionPartyFilter));
        }

        usort($vehicles, function ($a, $b) {
            if ($a['status'] !== $b['status']) {
                return $a['status'] === 'offline' ? -1 : 1;
            }
            return $b['seconds'] - $a['seconds'];
        });

        return $vehicles;
    }

    /**
     * Datetime-cast model attributes get tagged with config('app.timezone')
     * (UTC) by Eloquent on read, regardless of what timezone the value was
     * actually written in. Carbon::parse($castAttribute, 'Africa/Harare')
     * silently IGNORES the timezone argument when given an already-cast
     * DateTimeInterface — it only applies to raw strings. That mismatch
     * was inflating every offline duration by ~2 hours. This bypasses the
     * cast and parses the raw stored string directly, so the timezone
     * argument actually takes effect.
     */
    private function harareTime($model, string $attribute): ?Carbon
    {
        $raw = $model->getRawOriginal($attribute);
        return $raw ? Carbon::parse($raw, 'Africa/Harare') : null;
    }

    private function formatDuration(int $seconds): string
    {
        $seconds = abs($seconds);
        if ($seconds < 60)    return "{$seconds}s";
        if ($seconds < 3600)  return floor($seconds / 60) . 'm';
        if ($seconds < 86400) return floor($seconds / 3600) . 'h ' . (int) floor(($seconds % 3600) / 60) . 'm';
        $days  = (int) floor($seconds / 86400);
        $hours = (int) floor(($seconds % 86400) / 3600);
        return "{$days}d {$hours}h";
    }

    public function render(FleetStateCounter $counter)
    {
        // Offline = offline AND Functional (Active) AND Bantu Track; the rest is "excluded"
        $counts        = $counter->forClients([$this->clientId]);
        $totalCount    = $counts['total'];
        $onlineCount   = $counts['online'];
        $offlineCount  = $counts['offline'];
        $excludedCount = $counts['excluded'];

        $vehicles = $this->view === 'states' ? $this->buildVehicleStates() : [];

        // Always computed (cheap count query) so the tab badge is accurate
        // regardless of which tab is active — the full list below is still
        // only built when actually needed, to avoid wasted queries on poll.
        $reportsCount = AfisOfflineReportFile::where('client_id', $this->clientId)->count();

        $reports = $this->view === 'reports'
            ? AfisOfflineReportFile::where('client_id', $this->clientId)
                ->when($this->reportSearch, fn($q) => $q->where('title', 'like', "%{$this->reportSearch}%"))
                ->latest()
                ->get()
            : collect();

        $commentOptions = AfisOfflineIncident::COMMENTS;
        $partyOptions   = AfisOfflineIncident::ACTION_PARTIES;

        return view('afisportal::livewire.fleet-state-dashboard.index', compact(
            'vehicles', 'totalCount', 'onlineCount', 'offlineCount', 'excludedCount',
            'reports', 'reportsCount', 'commentOptions', 'partyOptions'
        ));
    }
}
