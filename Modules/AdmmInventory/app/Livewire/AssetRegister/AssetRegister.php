<?php

namespace Modules\AdmmInventory\Livewire\AssetRegister;

use Livewire\Component;
use Livewire\WithPagination;
use Modules\AdmmInventory\Models\AssetRecord;
use Modules\AuditLog\Services\AuditLogService;

class AssetRegister extends Component
{
    use WithPagination;

    // ─── Filters ──────────────────────────────────────────────────────────────
    public string $fInstallDate  = '';
    public string $fClient       = '';
    public string $fVehicleReg   = '';
    public string $fFleetNo      = '';
    public string $fVehicleMake  = '';
    public string $fImei         = '';
    public string $fDeviceName   = '';
    public string $fDeviceType   = '';
    public string $fConfig       = '';
    public string $fDeviceState  = '';
    public string $fSimSerial    = '';
    public string $fSimPhone     = '';
    public string $fSimType      = '';
    public string $fSimIsp       = '';
    public string $fLocation     = '';
    public string $fTechnician   = '';

    // ─── Save state ───────────────────────────────────────────────────────────
    public array  $changes       = [];
    public bool   $hasChanges    = false;

    public function updatedFClient()      { $this->resetPage(); }
    public function updatedFImei()        { $this->resetPage(); }
    public function updatedFVehicleReg()  { $this->resetPage(); }
    public function updatedFLocation()    { $this->resetPage(); }
    public function updatedFDeviceState() { $this->resetPage(); }
    public function updatedFSimIsp()      { $this->resetPage(); }
    public function updatedFDeviceType()  { $this->resetPage(); }

    // ─── Cell update ─────────────────────────────────────────────────────────
    public function updateCell(int $id, string $field, $value): void
    {
        if (!isset($this->changes[$id])) {
            $this->changes[$id] = [];
        }
        $this->changes[$id][$field] = $value;
        $this->hasChanges = true;
    }

    // ─── Save all changes ─────────────────────────────────────────────────────
    public function saveAll(): void
{
    $savedCount = 0;

    foreach ($this->changes as $id => $fields) {
        $record = AssetRecord::find($id);
        if (!$record) continue;

        $oldValues = array_intersect_key($record->toArray(), $fields);
        $record->update($fields);

        app(AuditLogService::class)->record(
            event:      'asset_record.updated',
            module:     'AdmmInventory',
            data:       ['old' => $oldValues, 'new' => $fields],
            entityType: AssetRecord::class,
            entityId:   $id,
        );

        $savedCount++;
    }

    $this->changes    = [];
    $this->hasChanges = false;

    $this->dispatch('changes-saved');
    session()->flash('success', "{$savedCount} record(s) saved successfully.");
}
    // ─── Discard changes ─────────────────────────────────────────────────────
    public function discardChanges(): void
    {
        $this->changes    = [];
        $this->hasChanges = false;
    }

    // ─── Add new row ─────────────────────────────────────────────────────────
    public function addRow(): void
    {
        AssetRecord::create([
            'gps_device_state' => 'ACTIVE',
            'location'         => 'STOCK',
        ]);

        session()->flash('success', 'New row added. Fill in the details and save.');
    }

    // ─── Delete row ──────────────────────────────────────────────────────────
    public function deleteRow(int $id): void
    {
        $record = AssetRecord::find($id);
        if (!$record) return;

        app(AuditLogService::class)->record(
            event:      'asset_record.deleted',
            module:     'AdmmInventory',
            data:       ['imei' => $record->gps_device_imei, 'client' => $record->client],
            entityType: AssetRecord::class,
            entityId:   $id,
        );

        $record->delete();
        unset($this->changes[$id]);
    }

    // ─── Render ──────────────────────────────────────────────────────────────
    public function render()
    {
        $records = AssetRecord::query()
            ->when($this->fInstallDate, fn($q) => $q->where('installation_date', 'like', "%{$this->fInstallDate}%"))
            ->when($this->fClient,      fn($q) => $q->where('client',             'like', "%{$this->fClient}%"))
            ->when($this->fVehicleReg,  fn($q) => $q->where('vehicle_reg_no',     'like', "%{$this->fVehicleReg}%"))
            ->when($this->fFleetNo,     fn($q) => $q->where('vehicle_fleet_no',   'like', "%{$this->fFleetNo}%"))
            ->when($this->fVehicleMake, fn($q) => $q->where('vehicle_make',       'like', "%{$this->fVehicleMake}%"))
            ->when($this->fImei,        fn($q) => $q->where('gps_device_imei',    'like', "%{$this->fImei}%"))
            ->when($this->fDeviceName,  fn($q) => $q->where('gps_device_name',    'like', "%{$this->fDeviceName}%"))
            ->when($this->fDeviceType,  fn($q) => $q->where('gps_device_type',    'like', "%{$this->fDeviceType}%"))
            ->when($this->fConfig,      fn($q) => $q->where('configuration',      'like', "%{$this->fConfig}%"))
            ->when($this->fDeviceState, fn($q) => $q->where('gps_device_state',    $this->fDeviceState))
            ->when($this->fSimSerial,   fn($q) => $q->where('sim_card_serial_no', 'like', "%{$this->fSimSerial}%"))
            ->when($this->fSimPhone,    fn($q) => $q->where('sim_card_phone_no',  'like', "%{$this->fSimPhone}%"))
            ->when($this->fSimType,     fn($q) => $q->where('sim_card_type',       $this->fSimType))
            ->when($this->fSimIsp,      fn($q) => $q->where('sim_card_isp',        $this->fSimIsp))
            ->when($this->fLocation,    fn($q) => $q->where('location',            $this->fLocation))
            ->when($this->fTechnician,  fn($q) => $q->where('technician',         'like', "%{$this->fTechnician}%"))
            ->orderByRaw("FIELD(location, 'CLIENT', 'STOCK', 'LOST')")
            ->orderBy('installation_date')
            ->get();

        $stats = [
            'total'  => AssetRecord::count(),
            'client' => AssetRecord::where('location', 'CLIENT')->count(),
            'stock'  => AssetRecord::where('location', 'STOCK')->count(),
            'lost'   => AssetRecord::where('location', 'LOST')->count(),
        ];

        return view('admminventory::livewire.asset-register.index', compact('records', 'stats'));
    }
}