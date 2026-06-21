<?php

namespace Modules\AdmmWorkflows\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\AuditLog\Models\AuditLog;

class WorkflowController extends Controller
{
    public function hub()
    {
        $recentActivity = AuditLog::with('user')
            ->whereIn('event', ['sim.swapped', 'device.installed', 'device.removed'])
            ->latest('created_at')
            ->limit(15)
            ->get();

        return view('admmworkflows::hub.index', compact('recentActivity'));
    }

    public function simSwap()
    {
        return view('admmworkflows::hub.sim-swap');
    }

    public function deviceInstall()
    {
        return view('admmworkflows::hub.device-install');
    }

    public function deviceRemove()
    {
        return view('admmworkflows::hub.device-remove');
    }
}