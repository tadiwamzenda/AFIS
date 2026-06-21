<?php

namespace Modules\AdmmWorkflows\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Modules\AdmmInventory\Models\GpsDevice;
use Modules\AdmmInventory\Models\SimCard;
use Modules\AdmmWorkflows\Services\WorkflowService;

class SimSwapWizard extends Component
{
    public int    $step      = 1;
    public ?int   $deviceId  = null;
    public ?int   $newSimId  = null;
    public string $reason    = '';
    public bool   $completed = false;
    public string $error     = '';

    public function selectDevice(): void
    {
        if (!$this->deviceId) {
            $this->error = 'Please select a device.';
            return;
        }
        $this->error = '';
        $this->step  = 2;
    }

    public function confirm(): void
    {
        if (!$this->newSimId) {
            $this->error = 'Please select a new SIM card.';
            return;
        }
        if (empty(trim($this->reason))) {
            $this->error = 'Please enter a reason for the swap.';
            return;
        }
        $this->error = '';
        $this->step  = 3;
    }

    public function execute(): void
    {
        try {
            $device = GpsDevice::findOrFail($this->deviceId);
            $newSim = SimCard::findOrFail($this->newSimId);

            app(WorkflowService::class)->simSwap(
                $device, $newSim, $this->reason, Auth::id()
            );

            $this->completed = true;
            $this->step      = 4;

        } catch (\Throwable $e) {
            $this->error = 'Swap failed: ' . $e->getMessage();
        }
    }

    public function render()
    {
        $devices = GpsDevice::with('simCard', 'client')
            ->whereNotIn('status', ['decommissioned', 'lost_stolen'])
            ->orderBy('serial_number')
            ->get();

        $availableSims = SimCard::whereIn('status', ['unassigned', 'active_internal'])
            ->whereIn('location_context', ['internal_stock', 'unallocated'])
            ->orderBy('msisdn')
            ->get();

        $selectedDevice = $this->deviceId ? GpsDevice::with('simCard', 'client')->find($this->deviceId) : null;
        $selectedSim    = $this->newSimId ? SimCard::find($this->newSimId) : null;

        return view('admmworkflows::livewire.sim-swap', compact(
            'devices', 'availableSims', 'selectedDevice', 'selectedSim'
        ));
    }
}