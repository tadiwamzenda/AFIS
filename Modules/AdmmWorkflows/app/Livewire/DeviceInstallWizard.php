<?php

namespace Modules\AdmmWorkflows\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Modules\AdmmInventory\Models\Client;
use Modules\AdmmInventory\Models\GpsDevice;
use Modules\AdmmInventory\Models\SimCard;
use Modules\AdmmWorkflows\Services\WorkflowService;

class DeviceInstallWizard extends Component
{
    public int    $step        = 1;
    public ?int   $deviceId    = null;
    public ?int   $clientId    = null;
    public string $vehicleReg  = '';
    public ?int   $simId       = null;
    public string $reason      = '';
    public bool   $completed   = false;
    public string $error       = '';

    public function selectDevice(): void
    {
        if (!$this->deviceId) { $this->error = 'Please select a device.'; return; }
        $this->error = '';
        $this->step  = 2;
    }

    public function confirm(): void
    {
        if (!$this->clientId)          { $this->error = 'Please select a client.'; return; }
        if (empty(trim($this->vehicleReg))) { $this->error = 'Please enter a vehicle registration.'; return; }
        if (empty(trim($this->reason)))    { $this->error = 'Please enter a reason.'; return; }
        $this->error = '';
        $this->step  = 3;
    }

    public function execute(): void
    {
        try {
            $device = GpsDevice::findOrFail($this->deviceId);
            $client = Client::findOrFail($this->clientId);
            $sim    = $this->simId ? SimCard::findOrFail($this->simId) : null;

            app(WorkflowService::class)->deviceInstall(
                $device, $client, $this->vehicleReg, $sim, $this->reason, Auth::id()
            );

            $this->completed = true;
            $this->step      = 4;

        } catch (\Throwable $e) {
            $this->error = 'Install failed: ' . $e->getMessage();
        }
    }

    public function render()
    {
        // NEW:
        $devices = GpsDevice::where('status', 'in_office_stock')
            ->orderBy('imei')
            ->get();

        $clients = Client::active()->orderBy('name')->get();

        $availableSims = SimCard::whereIn('status', ['unassigned', 'active_internal'])
            ->whereIn('location_context', ['internal_stock', 'unallocated'])
            ->orderBy('msisdn')
            ->get();

        $selectedDevice = $this->deviceId ? GpsDevice::find($this->deviceId) : null;
        $selectedClient = $this->clientId ? Client::find($this->clientId) : null;
        $selectedSim    = $this->simId    ? SimCard::find($this->simId)    : null;

        return view('admmworkflows::livewire.device-install', compact(
            'devices', 'clients', 'availableSims',
            'selectedDevice', 'selectedClient', 'selectedSim'
        ));
    }
}