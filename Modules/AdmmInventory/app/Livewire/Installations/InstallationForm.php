<?php

namespace Modules\AdmmInventory\Livewire\Installations;

use Livewire\Component;
use Modules\AdmmInventory\Models\Client;
use Modules\AdmmInventory\Models\GpsDevice;
use Modules\AdmmInventory\Models\Technician;
use Modules\AdmmInventory\Services\InstallationService;

class InstallationForm extends Component
{
    // Untyped: selects send '' for "none"
    public $techFilter = '';
    public $deviceId = '';
    public $clientId = '';
    public $installerId = '';
    public $replacedDeviceId = '';

    public string $deviceSearch = '';
    public string $type = 'new';              // new | repair_swap
    public string $installedAt = '';
    public string $registration = '';
    public string $fleetNumber = '';
    public string $make = '';
    public string $location = '';
    public string $replacedDeviceOutcome = 'faulty';
    public string $replacedSimOutcome = 'stock';
    public bool   $override = false;
    public string $overrideReason = '';

    public string $message = '';
    public string $error = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->isBtStaff(), 403);
        $this->installedAt = now()->timezone('Africa/Harare')->toDateString();
    }

    public function updatedDeviceId($value): void
    {
        $this->error = '';
        $d = $value ? GpsDevice::find((int) $value) : null;
        $this->installerId = $d?->technician_id ?? '';   // default: the custodian
    }

    public function updatedRegistration(): void
    {
        $this->replacedDeviceId = '';
        $this->override = false;
    }

    public function useMake(string $make): void
    {
        $this->make = $make;
    }

    public function switchToRepair(): void
    {
        $this->type = 'repair_swap';
        $this->override = false;
        $this->autoPickReplaced();
    }

    public function updatedType(): void
    {
        $this->override = false;
        $this->autoPickReplaced();
    }

    private function autoPickReplaced(): void
    {
        if ($this->type === 'repair_swap' && $this->registration !== '') {
            $c = app(InstallationService::class)->findConflicts($this->registration, $this->deviceId ? (int) $this->deviceId : null);
            $this->replacedDeviceId = $c->count() === 1 ? $c->first()->id : '';
        }
    }

    public function save(InstallationService $svc): void
    {
        abort_unless(auth()->user()?->isBtStaff(), 403);
        $this->message = $this->error = '';

        try {
            $r = $svc->install([
                'device_id'              => $this->deviceId,
                'client_id'              => $this->clientId,
                'installed_at'           => $this->installedAt,
                'vehicle_registration'   => $this->registration,
                'fleet_number'           => $this->fleetNumber,
                'vehicle_make'           => $this->make,
                'installation_location'  => $this->location,
                'type'                   => $this->type,
                'installer_id'           => $this->installerId,
                'replaced_device_id'     => $this->replacedDeviceId,
                'replaced_device_outcome'=> $this->replacedDeviceOutcome,
                'replaced_sim_outcome'   => $this->replacedSimOutcome,
                'override'               => $this->override,
                'override_reason'        => $this->overrideReason,
            ], auth()->id(), auth()->user()->role === \App\Models\User::ROLE_BT_ADMIN);

            $this->message = "Installed IMEI {$r['imei']} on {$r['registration']} for {$r['client']}.";
            $this->reset(['deviceId', 'registration', 'fleetNumber', 'make', 'location', 'replacedDeviceId',
                          'override', 'overrideReason', 'installerId']);
            $this->type = 'new';
            $this->replacedDeviceOutcome = 'faulty';
            $this->replacedSimOutcome = 'stock';
        } catch (\DomainException $e) {
            $this->error = $e->getMessage();
        }
    }

    public function render()
    {
        $svc = app(InstallationService::class);

        $devices = GpsDevice::with(['simCard', 'assignedTechnician'])
            ->where('status', 'with_technician')
            ->whereNotNull('sim_card_id')
            ->when($this->techFilter !== '' && $this->techFilter !== null, fn ($q) => $q->where('technician_id', (int) $this->techFilter))
            ->when(trim($this->deviceSearch) !== '', fn ($q) => $q->where('imei', 'like', '%' . trim($this->deviceSearch) . '%'))
            ->orderBy('imei')->limit(100)->get();

        $selected  = $this->deviceId ? GpsDevice::with(['simCard', 'assignedTechnician'])->find((int) $this->deviceId) : null;
        $conflicts = trim($this->registration) !== ''
            ? $svc->findConflicts($this->registration, $selected?->id)
            : collect();

        return view('admminventory::livewire.installations.form', [
            'technicians' => Technician::active()->orderBy('name')->get(),
            'clients'     => Client::active()->orderBy('name')->get(),
            'devices'     => $devices,
            'selected'    => $selected,
            'conflicts'   => $conflicts,
            'suggestions' => $this->clientId ? $svc->makeSuggestions((int) $this->clientId) : collect(),
            'locations'   => $this->locationOptions(),
            'makes'       => GpsDevice::whereNotNull('vehicle_make')->where('vehicle_make', '!=', '')->distinct()->orderBy('vehicle_make')->limit(300)->pluck('vehicle_make'),
            'isAdmin'     => auth()->user()?->role === \App\Models\User::ROLE_BT_ADMIN,
        ]);
    }

    private function locationOptions(): array
    {
        $used = GpsDevice::whereNotNull('installation_location')->where('installation_location', '!=', '')
            ->distinct()->pluck('installation_location')->all();

        return array_values(array_unique(array_merge(
            ['Dashboard', 'Under dashboard', 'Behind glovebox', 'Engine bay', 'Roof lining', 'Passenger footwell', 'Driver footwell', 'Near fuse box'],
            $used
        )));
    }
}
