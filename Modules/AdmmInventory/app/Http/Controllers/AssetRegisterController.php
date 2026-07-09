<?php

namespace Modules\AdmmInventory\Http\Controllers;

use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Modules\AdmmInventory\Models\AssetRecord;

class AssetRegisterController extends Controller
{
   public function exportPdf(Request $request)
{
    ini_set('memory_limit', '512M');
    set_time_limit(180);

    $records = AssetRecord::query()
        ->when($request->fInstallDate, fn($q) => $q->where('installation_date', 'like', "%{$request->fInstallDate}%"))
        ->when($request->fClient,      fn($q) => $q->where('client',             'like', "%{$request->fClient}%"))
        ->when($request->fVehicleReg,  fn($q) => $q->where('vehicle_reg_no',     'like', "%{$request->fVehicleReg}%"))
        ->when($request->fFleetNo,     fn($q) => $q->where('vehicle_fleet_no',   'like', "%{$request->fFleetNo}%"))
        ->when($request->fVehicleMake, fn($q) => $q->where('vehicle_make',       'like', "%{$request->fVehicleMake}%"))
        ->when($request->fImei,        fn($q) => $q->where('gps_device_imei',    'like', "%{$request->fImei}%"))
        ->when($request->fDeviceName,  fn($q) => $q->where('gps_device_name',    'like', "%{$request->fDeviceName}%"))
        ->when($request->fDeviceType,  fn($q) => $q->where('gps_device_type',    'like', "%{$request->fDeviceType}%"))
        ->when($request->fConfig,      fn($q) => $q->where('configuration',      'like', "%{$request->fConfig}%"))
        ->when($request->fDeviceState, fn($q) => $q->where('gps_device_state',    $request->fDeviceState))
        ->when($request->fSimSerial,   fn($q) => $q->where('sim_card_serial_no', 'like', "%{$request->fSimSerial}%"))
        ->when($request->fSimPhone,    fn($q) => $q->where('sim_card_phone_no',  'like', "%{$request->fSimPhone}%"))
        ->when($request->fSimType,     fn($q) => $q->where('sim_card_type',       $request->fSimType))
        ->when($request->fSimIsp,      fn($q) => $q->where('sim_card_isp',        $request->fSimIsp))
        ->when($request->fLocation,    fn($q) => $q->where('location',            $request->fLocation))
        ->when($request->fTechnician,  fn($q) => $q->where('technician',         'like', "%{$request->fTechnician}%"))
        ->orderByRaw("FIELD(location, 'CLIENT', 'STOCK', 'LOST')")
        ->orderByRaw("CASE WHEN installation_date IS NULL THEN 1 ELSE 0 END")
        ->orderBy('installation_date')
        ->orderBy('id')
        ->get();

    $filters = [];
    if ($request->fClient)      $filters[] = "Client: {$request->fClient}";
    if ($request->fLocation)    $filters[] = "Location: {$request->fLocation}";
    if ($request->fDeviceState) $filters[] = "State: {$request->fDeviceState}";
    if ($request->fVehicleReg)  $filters[] = "Vehicle Reg: {$request->fVehicleReg}";
    if ($request->fImei)        $filters[] = "IMEI: {$request->fImei}";
    if ($request->fSimPhone)    $filters[] = "SIM Phone: {$request->fSimPhone}";

    // Return printable HTML — browser handles PDF conversion via print dialog
    return view('admminventory::asset-register.export-pdf', [
        'records'    => $records,
        'filters'    => $filters,
        'generated'  => now()->format('d M Y H:i'),
        'totalCount' => $records->count(),
        'printMode'  => true,
    ]);
}
}