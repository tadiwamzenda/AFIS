<?php

namespace Modules\AfisPortal\Livewire;

use Carbon\Carbon;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Modules\AfisPipeline\Models\AfisTracker;
use Modules\AfisPipeline\Models\AfisDeviceAlert;
use Modules\AfisPipeline\Models\AfisTrackerGroup;
use Modules\AfisPipeline\Models\AfisOfflineIncident;
use Modules\AfisPipeline\Models\AfisOfflineReportFile;
use Modules\AuditLog\Models\AuditLog;

class FleetStateDashboard extends Component
{
    public int    $clientId;
    public string $view           = 'states'; // 'states' | 'reports'
    public string $filter         = 'all';
    public string $durationFilter = '';
    public string $commentFilter  = '';
    public string $search         = '';
    public string $reportSearch   = '';
    public array  $comments       = [];
    public array  $resolutions    = [];

    public function mount(int $clientId): void
    {
        $this->clientId = $clientId;

        if (request('tab') === 'reports') {
            $this->view = 'reports';
        }

        $openIncidents = AfisOfflineIncident::whereHas('tracker', fn($q) => $q->where('client_id', $clientId))
            ->open()
            ->get();

        foreach ($openIncidents as $incident) {
            $this->comments[$incident->id]    = $incident->comment ?? '';
            $this->resolutions[$incident->id] = $incident->resolution ?? '';
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

    private function durationBucket(int $seconds): string
    {
        if ($seconds < 3600)   return 'none';
        if ($seconds < 86400)  return 'hourly';
        if ($seconds < 604800) return 'daily';
        return 'weekly';
    }

    public function saveUpdates(): void
    {
        $changed = 0;

        foreach ($this->comments as $incidentId => $comment) {
            $incident = AfisOfflineIncident::find($incidentId);
            if (!$incident) continue;

            $resolution = $this->resolutions[$incidentId] ?? null;
            $comment    = $comment ?: null;
            $resolution = $resolution ?: null;

            if ($comment === $incident->comment && $resolution === $incident->resolution) {
                continue;
            }

            $before = ['comment' => $incident->comment, 'resolution' => $incident->resolution];
            $incident->update(['comment' => $comment, 'resolution' => $resolution]);

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
                    'after'         => ['comment' => $comment, 'resolution' => $resolution],
                ],
            ]);

            $changed++;
        }

        session()->flash('success', $changed > 0
            ? "{$changed} vehicle record(s) updated."
            : 'No changes to save.');
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
            ];
        }

        if ($this->filter !== 'all') {
            $vehicles = array_values(array_filter($vehicles, fn($v) => $v['status'] === $this->filter));
        }

        if ($this->durationFilter !== '') {
            $vehicles = array_values(array_filter($vehicles, fn($v) => $v['bucket'] === $this->durationFilter));
        }

        if ($this->commentFilter !== '') {
            $vehicles = array_values(array_filter($vehicles, function ($v) {
                if (!$v['incident_id']) return false;
                $incident = AfisOfflineIncident::find($v['incident_id']);
                return $incident?->comment === $this->commentFilter;
            }));
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

    public function render()
    {
        $allTrackers  = AfisTracker::where('client_id', $this->clientId)->get();
        $totalCount   = $allTrackers->count();
        $onlineCount  = $allTrackers->where('online_status', 'online')->count();
        $offlineCount = $allTrackers->where('online_status', 'offline')->count();

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

        return view('afisportal::livewire.fleet-state-dashboard.index', compact(
            'vehicles', 'totalCount', 'onlineCount', 'offlineCount', 'reports', 'reportsCount'
        ));
    }
}