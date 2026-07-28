<?php

namespace Modules\Notifications\Livewire;

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
    public bool   $unreadOnly     = false;

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
        $notifications = AfisNotification::query()
            ->when($this->severityFilter, fn($q) => $q->where('severity', $this->severityFilter))
            ->when($this->clientFilter,   fn($q) => $q->where('data->client', $this->clientFilter))
            ->when($this->unreadOnly,     fn($q) => $q->whereNull('read_at'))
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