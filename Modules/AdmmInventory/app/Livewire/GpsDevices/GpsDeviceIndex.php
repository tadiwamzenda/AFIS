<?php

namespace Modules\AdmmInventory\Livewire\GpsDevices;

use Livewire\Component;
use Livewire\WithPagination;
use Modules\AdmmInventory\Models\GpsDevice;

class GpsDeviceIndex extends Component
{
    use WithPagination;

    public string $search        = '';
    public string $statusFilter  = '';
    public string $contextFilter = '';

    protected $queryString = [
        'search'        => ['except' => ''],
        'statusFilter'  => ['except' => ''],
        'contextFilter' => ['except' => ''],
    ];

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingStatusFilter(): void { $this->resetPage(); }
    public function updatingContextFilter(): void { $this->resetPage(); }

    public function render()
    {
        $devices = GpsDevice::query()
            ->with(['client', 'simCard'])
            ->when($this->search, fn($q) => $q->where(function ($q) {
                $q->where('serial_number', 'like', "%{$this->search}%")
                  ->orWhere('model', 'like', "%{$this->search}%")
                  ->orWhere('vehicle_registration', 'like', "%{$this->search}%");
            }))
            ->when($this->statusFilter,  fn($q) => $q->where('status',           $this->statusFilter))
            ->when($this->contextFilter, fn($q) => $q->where('location_context', $this->contextFilter))
            ->latest()
            ->paginate(20);

        return view('admminventory::livewire.gps-devices.index', compact('devices'));
    }
}