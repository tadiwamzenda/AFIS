<?php

namespace Modules\AdmmInventory\Livewire\Accessories;

use Livewire\Component;
use Livewire\WithPagination;
use Modules\AdmmInventory\Models\Accessory;

class AccessoryIndex extends Component
{
    use WithPagination;

    public string $search        = '';
    public string $statusFilter  = '';
    public string $contextFilter = '';
    public string $typeFilter    = '';

    protected $queryString = [
        'search'        => ['except' => ''],
        'statusFilter'  => ['except' => ''],
        'contextFilter' => ['except' => ''],
        'typeFilter'    => ['except' => ''],
    ];

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingStatusFilter(): void { $this->resetPage(); }
    public function updatingContextFilter(): void { $this->resetPage(); }
    public function updatingTypeFilter(): void { $this->resetPage(); }

    public function render()
    {
        $accessories = Accessory::query()
            ->with(['accessoryType', 'client', 'gpsDevice'])
            ->when($this->search, fn($q) => $q->where('serial_number', 'like', "%{$this->search}%"))
            ->when($this->statusFilter,  fn($q) => $q->where('status',            $this->statusFilter))
            ->when($this->contextFilter, fn($q) => $q->where('location_context',  $this->contextFilter))
            ->when($this->typeFilter,    fn($q) => $q->where('accessory_type_id', $this->typeFilter))
            ->latest()
            ->paginate(20);

        $types = \Modules\AdmmInventory\Models\AccessoryType::active()->orderBy('name')->get();

        return view('admminventory::livewire.accessories.index', compact('accessories', 'types'));
    }
}