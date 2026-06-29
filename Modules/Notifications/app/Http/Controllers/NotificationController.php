<?php

namespace Modules\Notifications\Http\Controllers;

use App\Http\Controllers\Controller;

class NotificationController extends Controller
{
    public function index()
    {
        return view('notifications::notifications-page');
    }
}