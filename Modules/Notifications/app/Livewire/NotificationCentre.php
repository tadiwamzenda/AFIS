<?php

namespace Modules\Notifications\Livewire;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\AdmmInventory\Models\Client;
use Modules\Notifications\Models\AfisNotification;
use Modules\Notifications\Services\NotificationService;

class NotificationCentre extends Component
{
    use WithPagination;

    public string $severityFilter = '';
    public string $clientFilter   = '';
    public string $statusFilter   = ''; // '' | 'pending' | 'attended'
    public string $search         = '';
    public bool   $unreadOnly     = false;
    public string $durationFilter = '';

    public function markRead(int $id, NotificationService $service): void
    {
        $service->markAsRead($id);
    }

    public function markAllRead(NotificationService $service): void
    {
        $service->markAllAsRead(Auth::id());
        session()->flash('success', 'All notifications marked as read.');
    }

    public function render()
    {
        // Same integer-hour bucket boundaries as everywhere else this
        // feature touches (FleetStateDashboard, NotificationReportController):
        // hourly=1-23h, daily=24-167h, weekly=168h+. offline_hours lives
        // inside the JSON data column, so this needs a raw JSON extract —
        // done at the SQL level (not a post-fetch PHP filter) specifically
        // so pagination counts stay accurate.
        $notifications = AfisNotification::query()
            ->when($this->severityFilter, fn($q) => $q->where('severity', $this->severityFilter))
            ->when($this->clientFilter,   fn($q) => $q->where('data->client', $this->clientFilter))
            ->when($this->statusFilter,   fn($q) => $q->where('status', $this->statusFilter))
            ->when($this->unreadOnly,     fn($q) => $q->whereNull('read_at'))
            ->when($this->durationFilter === 'hourly', fn($q) => $q->whereRaw("CAST(JSON_UNQUOTE(JSON_EXTRACT(data, '$.offline_hours')) AS UNSIGNED) BETWEEN 1 AND 23"))
            ->when($this->durationFilter === 'daily',  fn($q) => $q->whereRaw("CAST(JSON_UNQUOTE(JSON_EXTRACT(data, '$.offline_hours')) AS UNSIGNED) BETWEEN 24 AND 167"))
            ->when($this->durationFilter === 'weekly', fn($q) => $q->whereRaw("CAST(JSON_UNQUOTE(JSON_EXTRACT(data, '$.offline_hours')) AS UNSIGNED) >= 168"))
            ->when($this->search, function ($q) {
                // Vehicle name lives under different JSON keys depending on
                // notification type (tracker_label for offline alerts,
                // vehicle_label for fuel drain) — search both, plus fall
                // back to title/message for notification types with no
                // specific vehicle at all (e.g. pipeline sync failures).
                $q->where(function ($sub) {
                    $sub->where('data->tracker_label', 'like', "%{$this->search}%")
                        ->orWhere('data->vehicle_label', 'like', "%{$this->search}%")
                        ->orWhere('title', 'like', "%{$this->search}%")
                        ->orWhere('message', 'like', "%{$this->search}%");
                });
            })
            ->latest('created_at')
            ->paginate(20);

        $unreadCount = AfisNotification::whereNull('read_at')->count();

        $clients = Client::where('is_active', true)
            ->where('id', '!=', 21)
            ->orderBy('name')
            ->pluck('name');

        return view('notifications::livewire.notification-centre', compact(
            'notifications', 'unreadCount', 'clients'
        ));
    }
}