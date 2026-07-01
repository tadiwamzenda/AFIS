<?php

namespace Modules\AdmmWorkflows\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Modules\AdmmInventory\Models\GpsDevice;
use Modules\AdmmWorkflows\Services\WorkflowService;

class DeviceRemoveWizard extends Component
{
    public int    $step       = 1;
    public ?int   $deviceId   = null;
    public string $reason     = '';
    public bool   $detachSim  = false;
    public bool   $completed  = false;
    public string $error      = '';

    public function selectDevice(): void
    {
        if (!$this->deviceId) { $this->error = 'Please select a device.'; return; }
        $this->error = '';
        $this->step  = 2;
    }

    public function confirm(): void
    {
        if (empty(trim($this->reason))) { $this->error = 'Please enter a reason for removal.'; return; }
        $this->error = '';
        $this->step  = 3;
    }

    public function execute(): void
    {
        try {
            $device = GpsDevice::findOrFail($this->deviceId);

            app(WorkflowService::class)->deviceRemove(
                $device, $this->reason, $this->detachSim, Auth::id()
            );

            $this->completed = true;
            $this->step      = 4;

        } catch (\Throwable $e) {
            $this->error = 'Removal failed: ' . $e->getMessage();
        }
    }

    public function render()
{
    $devices = GpsDevice::with('simCard', 'client')
        ->where('status', 'installed')
        ->orderBy('imei')
        ->get();

    $selectedDevice = $this->deviceId
        ? GpsDevice::with('simCard', 'client')->find($this->deviceId)
        : null;

    return view('admmworkflows::livewire.device-remove', compact('devices', 'selectedDevice'));
}
}