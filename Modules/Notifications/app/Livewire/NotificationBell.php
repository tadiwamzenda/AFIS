<?php

namespace Modules\Notifications\Livewire;

use Livewire\Component;
use Modules\Notifications\Models\AfisNotification;

class NotificationBell extends Component
{
    public function render()
    {
        $unreadCount    = AfisNotification::whereNull('read_at')->count();
        $latestFive     = AfisNotification::whereNull('read_at')
            ->latest('created_at')
            ->limit(5)
            ->get();

        return view('notifications::livewire.notification-bell', compact('unreadCount', 'latestFive'));
    }
}