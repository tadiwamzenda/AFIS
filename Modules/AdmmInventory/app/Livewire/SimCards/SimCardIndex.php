<?php

namespace Modules\AdmmInventory\Livewire\SimCards;

use Livewire\Component;
use Livewire\WithPagination;
use Modules\AdmmInventory\Models\SimCard;

class SimCardIndex extends Component
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

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingContextFilter(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $simCards = SimCard::query()
            ->with('client')
            ->when($this->search, fn($q) => $q->where(function ($q) {
                $q->where('iccid', 'like', "%{$this->search}%")
                  ->orWhere('msisdn', 'like', "%{$this->search}%")
                  ->orWhere('network_provider', 'like', "%{$this->search}%");
            }))
            ->when($this->statusFilter,  fn($q) => $q->where('status',           $this->statusFilter))
            ->when($this->contextFilter, fn($q) => $q->where('location_context', $this->contextFilter))
            ->latest()
            ->paginate(20);

        return view('admminventory::livewire.sim-cards.index', [
            'simCards' => $simCards,
        ]);
    }
}