<?php

namespace Modules\AdmmInventory\Livewire\AssetRegister;

use Livewire\Component;
use Livewire\WithPagination;
use Modules\AdmmInventory\Models\AssetRecord;
use Modules\AuditLog\Services\AuditLogService;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

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

    // ─── Export Excel ─────────────────────────────────────────────────
    public function exportExcel()
    {
        $records = $this->getFilteredQuery()->get();

        return Excel::download(
            new class($records) implements FromCollection, WithHeadings, WithStyles {
                public function __construct(private $records) {}

                public function collection()
                {
                    return $this->records->map(fn($r) => [
                        $r->installation_date?->format('d/m/Y') ?? '',
                        $r->client ?? '',
                        $r->vehicle_reg_no ?? '',
                        $r->vehicle_fleet_no ?? '',
                        $r->vehicle_make ?? '',
                        $r->gps_device_imei ?? '',
                        $r->gps_device_name ?? '',
                        $r->gps_device_type ?? '',
                        $r->configuration ?? '',
                        $r->gps_device_state ?? '',
                        $r->sim_card_serial_no ?? '',
                        $r->sim_card_phone_no ?? '',
                        $r->sim_card_type ?? '',
                        $r->sim_card_isp ?? '',
                        $r->location ?? '',
                        $r->technician ?? '',
                        $r->comment ?? '',
                        $r->client_name ?? '',
                        $r->client_contact ?? '',
                        $r->client_email ?? '',
                    ]);
                }

                public function headings(): array
                {
                    return [
                        'Installation Date', 'Client', 'Vehicle Reg No', 'Vehicle Fleet No',
                        'Vehicle Make', 'GPS Device IMEI', 'GPS Device Name', 'GPS Device Type',
                        'Configuration', 'GPS Device State', 'Sim Card Serial No', 'Sim Card Phone No',
                        'Sim Card Type', 'SIM Card ISP', 'Location', 'Technician(Installer)',
                        'Comment', 'Client Name', 'Client Contact', 'Client Email',
                    ];
                }

                public function styles(Worksheet $sheet)
                {
                    return [
                        1 => [
                            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                            'fill' => [
                                'fillType'   => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                                'startColor' => ['rgb' => '1a5c4a'],
                            ],
                        ],
                    ];
                }
            },
            'asset-register-' . now()->format('Y-m-d') . '.xlsx'
        );
    }

    // ─── Export PDF (redirects to controller to avoid Livewire limits) ─
    public function exportPdf()
    {
        $params = http_build_query([
            'fInstallDate' => $this->fInstallDate,
            'fClient'      => $this->fClient,
            'fVehicleReg'  => $this->fVehicleReg,
            'fFleetNo'     => $this->fFleetNo,
            'fVehicleMake' => $this->fVehicleMake,
            'fImei'        => $this->fImei,
            'fDeviceName'  => $this->fDeviceName,
            'fDeviceType'  => $this->fDeviceType,
            'fConfig'      => $this->fConfig,
            'fDeviceState' => $this->fDeviceState,
            'fSimSerial'   => $this->fSimSerial,
            'fSimPhone'    => $this->fSimPhone,
            'fSimType'     => $this->fSimType,
            'fSimIsp'      => $this->fSimIsp,
            'fTechnician'  => $this->fTechnician,
        ]);
        return redirect(route('admin.admm.asset-register.export-pdf') . '?' . $params);
    }

    // ─── Shared filtered query ────────────────────────────────────────
    private function getFilteredQuery()
    {
        return AssetRecord::query()
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
            ->orderByRaw("CASE WHEN installation_date IS NULL THEN 1 ELSE 0 END")
            ->orderBy('installation_date')
            ->orderBy('id');
    }

    // ─── Render ──────────────────────────────────────────────────────────────

    public function render()
    {
        $records = $this->getFilteredQuery()->get();

        $allRecords = AssetRecord::query();
        $simsQuery  = AssetRecord::whereNotNull('sim_card_phone_no')->where('sim_card_phone_no', '!=', '');

        $stats = [
            'total'          => AssetRecord::count(),
            'devices_client' => AssetRecord::where('location', 'CLIENT')->count(),
            'devices_stock'  => AssetRecord::where('location', 'STOCK')->count(),
            'sims_client'    => $simsQuery->clone()->where('location', 'CLIENT')->count(),
            'sims_stock'     => $simsQuery->clone()->where('location', 'STOCK')->count(),
            'lost'           => AssetRecord::where('location', 'LOST')->count(),
        ];

        return view('admminventory::livewire.asset-register.index', compact('records', 'stats'));
    }
}