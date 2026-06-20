<?php

namespace Modules\AdmmDashboard\Http\Controllers;

use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    public function index()
    {
        return view('admmdashboard::index');
    }
}