<?php

namespace Modules\AdmmInventory\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\AdmmInventory\Models\GpsDevice;

class GpsDeviceController extends Controller
{
    public function index()
    {
        return view('admminventory::gps-devices.index');
    }

    public function create()
    {
        return view('admminventory::gps-devices.create');
    }

    public function edit(GpsDevice $device)
    {
        return view('admminventory::gps-devices.edit', compact('device'));
    }
}