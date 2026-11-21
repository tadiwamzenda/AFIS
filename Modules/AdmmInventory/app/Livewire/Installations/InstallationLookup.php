<?php

namespace Modules\AdmmInventory\Livewire\Installations;

use Livewire\Component;
use Livewire\WithPagination;
use Modules\AdmmInventory\Livewire\Concerns\HasPeriodFilter;
use Modules\AdmmInventory\Models\Client;
use Modules\AdmmInventory\Models\GpsDevice;
use Modules\AdmmInventory\Models\InstallationEvent;
use Modules\AdmmInventory\Models\Technician;
use Modules\AdmmInventory\Services\InstallationService;

class InstallationLookup extends Component
{
    use WithPagination, HasPeriodFilter;

    private const PER_PAGE = 25;

    public string $search = '';
    public $clientFilter = '';

    // One panel open at a time: edit | installer | uncouple | history
    public $activeId = null;
    public string $panel = '';

    // edit
    public string $editDate = '';
    public $editClient = '';
    public string $editReg = '';
    public string $editFleet = '';
    public string $editMake = '';
    public string $editLocation = '';

    // installer
    public $newInstaller = '';

    // uncouple
    public string $deviceOutcome = 'faulty';
    public string $simOutcome = 'stock';
    public string $reason = '';

    public string $message = '';
    public string $error = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->isBtStaff(), 403);
    }

    public function updatingSearch(): void       { $this->resetPage(); }
    public function updatingClientFilter(): void { $this->resetPage(); }

    public function open(int $id, string $panel): void
    {
        abort_unless(auth()->user()?->isBtStaff(), 403);
        $this->message = $this->error = '';

        if ($this->activeId === $id && $this->panel === $panel) {
            $this->closePanel();
            return;
        }

        $d = GpsDevice::findOrFail($id);
        $this->activeId = $id;
        $this->panel    = $panel;

        if ($panel === 'edit') {
            $this->editDate     = $d->installed_at?->format('Y-m-d') ?? '';
            $this->editClient   = $d->client_id ?? '';
            $this->editReg      = (string) $d->vehicle_registration;
            $this->editFleet    = (string) $d->fleet_number;
            $this->editMake     = (string) $d->vehicle_make;
            $this->editLocation = (string) $d->installation_location;
        } elseif ($panel === 'installer') {
            $this->newInstaller = $d->technician_id ?? '';
        } elseif ($panel === 'uncouple') {
            $this->deviceOutcome = 'faulty';
            $this->simOutcome    = 'stock';
            $this->reason        = '';
        }
    }

    public function closePanel(): void
    {
        $this->activeId = null;
        $this->panel = '';
    }

    public function saveEdit(InstallationService $svc): void
    {
        $this->run(function () use ($svc) {
            $changes = $svc->updateDetails((int) $this->activeId, [
                'installed_at'          => $this->editDate,
                'client_id'             => $this->editClient,
                'vehicle_registration'  => $this->editReg,
                'fleet_number'          => $this->editFleet,
                'vehicle_make'          => $this->editMake,
                'installation_location' => $this->editLocation,
            ], auth()->id());
            return 'Saved: ' . implode('; ', $changes);
        });
    }

    public function saveInstaller(InstallationService $svc): void
    {
        $this->run(function () use ($svc) {
            $svc->changeInstaller((int) $this->activeId, (int) $this->newInstaller, auth()->id());
            return 'Installer updated.';
        });
    }

    public function uncouple(InstallationService $svc): void
    {
        $this->run(function () use ($svc) {
            $r = $svc->uncouple((int) $this->activeId, $this->deviceOutcome, $this->simOutcome, $this->reason, auth()->id());
            return "IMEI {$r['imei']} removed from {$r['registration']}.";
        });
    }

    private function run(callable $action): void
    {
        abort_unless(auth()->user()?->isBtStaff(), 403);
        $this->message = $this->error = '';

        try {
            $this->message = $action();
            $this->closePanel();
        } catch (\DomainException $e) {
            $this->error = $e->getMessage();
        }
    }

    /** One query for the screen and the CSV, so both always agree. */
    private function query(): \Illuminate\Database\Eloquent\Builder
    {
        $term = trim($this->search);
        $key  = InstallationService::regKey($term);

        $q = GpsDevice::with(['client', 'simCard', 'assignedTechnician'])
            ->where('status', 'installed')
            ->when($this->clientFilter !== '' && $this->clientFilter !== null, fn ($q) => $q->where('client_id', (int) $this->clientFilter))
            ->when($term !== '', function ($q) use ($term, $key) {
                $q->where(function ($w) use ($term, $key) {
                    $w->where('imei', 'like', "%{$term}%")
                      ->orWhere('fleet_number', 'like', "%{$term}%")
                      ->orWhere('vehicle_registration', 'like', "%{$term}%")
                      ->orWhereHas('client', fn ($c) => $c->where('name', 'like', "%{$term}%"))
                      ->orWhereHas('simCard', fn ($s) => $s->where('iccid', 'like', "%{$term}%")->orWhere('msisdn', 'like', "%{$term}%"));
                    if ($key !== '') {
                        $w->orWhereRaw("UPPER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(vehicle_registration,' ',''),'-',''),'.',''),'/',''),'_','')) LIKE ?", ["%{$key}%"]);
                    }
                });
            });

        // installed_at is a DATE column
        return $this->applyPeriod($q, 'installed_at', true)
            ->orderByDesc('installed_at')->orderByDesc('id');
    }

    public function exportCsv()
    {
        abort_unless(auth()->user()?->isBtStaff(), 403);

        $q = $this->query();
        if ($this->periodError) {
            return null;
        }

        $name = 'installations_' . now()->timezone('Africa/Harare')->format('Ymd_Hi') . '.csv';

        return response()->streamDownload(function () use ($q) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Registration', 'Fleet number', 'Client', 'Make / model', 'IMEI', 'Device type', 'ICCID', 'Phone', 'Network', 'Installation date', 'Installer', 'Location in vehicle'], ',', '"', '');

            $q->chunk(500, function ($rows) use ($out) {
                foreach ($rows as $d) {
                    fputcsv($out, [
                        $this->csvSafe($d->vehicle_registration),
                        $this->csvSafe($d->fleet_number),
                        $this->csvSafe($d->client?->name),
                        $this->csvSafe($d->vehicle_make),
                        $d->imei,
                        $this->csvSafe($d->device_type),
                        $d->simCard?->iccid,
                        $d->simCard?->msisdn,
                        $d->simCard?->network_provider,
                        $d->installed_at?->format('Y-m-d'),
                        $this->csvSafe($d->assignedTechnician?->name ?? $d->technician),
                        $this->csvSafe($d->installation_location),
                    ], ',', '"', '');
                }
            });
            fclose($out);
        }, $name, ['Content-Type' => 'text/csv']);
    }

    public function render()
    {
        $rows = $this->query()->paginate(self::PER_PAGE);

        $history = collect();
        if ($this->activeId && $this->panel === 'history') {
            $history = InstallationEvent::with('technician')
                ->where('gps_device_id', $this->activeId)
                ->orderByDesc('id')->limit(100)->get();
        }

        $clients = Client::active()->orderBy('name')->get();

        return view('admminventory::livewire.installations.lookup', [
            'rows'        => $rows,
            'history'     => $history,
            'clientNames' => Client::whereIn('id', $history->pluck('client_id')->filter()->unique())->pluck('name', 'id'),
            'userNames'   => \App\Models\User::whereIn('id', $history->pluck('performed_by')->filter()->unique())->pluck('name', 'id'),
            'clients'     => $clients,
            'technicians' => Technician::active()->orderBy('name')->get(),
            'makes'       => GpsDevice::whereNotNull('vehicle_make')->where('vehicle_make', '!=', '')->distinct()->orderBy('vehicle_make')->limit(300)->pluck('vehicle_make'),
            'locations'   => GpsDevice::whereNotNull('installation_location')->where('installation_location', '!=', '')->distinct()->pluck('installation_location'),
        ]);
    }
}
