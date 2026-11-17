<?php

namespace Modules\AfisPortal\Livewire;

use Carbon\Carbon;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Modules\AdmmInventory\Models\VehicleNote;
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

    // Vehicle status notes
    public bool   $hideNoted   = false;     // hide OFFLINE vehicles that have an active note
    public $noteTrackerId      = null;      // vehicle whose "add note" panel is open
    public string $noteTag     = 'garage_repairs';
    public string $noteText    = '';
    public string $noteExpires = '';
    public string $noteError   = '';

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

    // ─── Vehicle status notes (BT staff write, everyone on the page reads) ───

    private function isStaff(): bool
    {
        return (bool) Auth::user()?->isBtStaff();
    }

    public function openNote(int $trackerId): void
    {
        abort_unless($this->isStaff(), 403);

        if ($this->noteTrackerId === $trackerId) {
            $this->closeNote();
            return;
        }

        abort_unless(AfisTracker::where('client_id', $this->clientId)->whereKey($trackerId)->exists(), 404);

        $this->noteTrackerId = $trackerId;
        $this->noteTag = 'garage_repairs';
        $this->noteText = $this->noteExpires = $this->noteError = '';
    }

    public function closeNote(): void
    {
        $this->noteTrackerId = null;
        $this->noteError = '';
    }

    public function saveNote(): void
    {
        abort_unless($this->isStaff(), 403);
        $this->noteError = '';

        $text = trim($this->noteText);
        if (!$this->noteTrackerId
            || !AfisTracker::where('client_id', $this->clientId)->whereKey((int) $this->noteTrackerId)->exists()) {
            $this->noteError = 'Vehicle not found.';
            return;
        }
        if (!array_key_exists($this->noteTag, VehicleNote::TAGS)) {
            $this->noteError = 'Choose a tag.';
            return;
        }
        if (mb_strlen($text) < 3 || mb_strlen($text) > 500) {
            $this->noteError = 'Write a note of 3 to 500 characters.';
            return;
        }
        $expires = null;
        if ($this->noteExpires !== '') {
            try {
                $expires = Carbon::parse($this->noteExpires)->toDateString();
            } catch (\Throwable) {
                $this->noteError = 'Invalid review-by date.';
                return;
            }
            if ($expires < now()->timezone('Africa/Harare')->toDateString()) {
                $this->noteError = 'The review-by date cannot be in the past.';
                return;
            }
        }

        // A new note replaces the previous active one (the old one stays as history)
        VehicleNote::active()->where('tracker_id', $this->noteTrackerId)
            ->update(['resolved_at' => now(), 'resolved_by' => Auth::id()]);

        VehicleNote::create([
            'tracker_id' => (int) $this->noteTrackerId,
            'client_id'  => $this->clientId,
            'tag'        => $this->noteTag,
            'note'       => $text,
            'expires_at' => $expires,
            'created_by' => Auth::id(),
        ]);

        $this->closeNote();
    }

    public function resolveNote(int $noteId): void
    {
        abort_unless($this->isStaff(), 403);

        VehicleNote::active()->where('client_id', $this->clientId)->whereKey($noteId)
            ->update(['resolved_at' => now(), 'resolved_by' => Auth::id()]);
    }

    private function activeNotes(): \Illuminate\Support\Collection
    {
        // oldest first, so keyBy keeps the newest note per vehicle
        return VehicleNote::active()->where('client_id', $this->clientId)->orderBy('id')->get()->keyBy('tracker_id');
    }

    public function deleteReport(int $id): void
    {
        $report = AfisOfflineReportFile::findOrFail($id);
        Storage::disk('local')->delete($report->file_path);
        $report->delete();
        session()->flash('success', 'Report deleted.');
    }

    private function buildVehicleStates($notes): array
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
                'note'        => $notes->get($tracker->id),
            ];
        }

        if ($this->filter !== 'all') {
            $vehicles = array_values(array_filter($vehicles, fn($v) => $v['status'] === $this->filter));
        }

        if ($this->hideNoted) {
            $vehicles = array_values(array_filter($vehicles, fn($v) => !($v['status'] === 'offline' && $v['note'])));
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

        $notes = $this->activeNotes();
        $notedOfflineCount = $allTrackers->where('online_status', 'offline')->filter(fn($t) => $notes->has($t->id))->count();
        $isStaff   = $this->isStaff();
        $tags      = VehicleNote::TAGS;
        $noteUsers = \App\Models\User::whereIn('id', $notes->pluck('created_by')->filter()->unique())->pluck('name', 'id');

        $vehicles = $this->view === 'states' ? $this->buildVehicleStates($notes) : [];

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
            'vehicles', 'totalCount', 'onlineCount', 'offlineCount', 'reports', 'reportsCount',
            'notedOfflineCount', 'isStaff', 'tags', 'noteUsers'
        ));
    }
}