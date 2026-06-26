<?php

namespace Modules\AfisIncidents\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Modules\AfisIncidents\Models\AfisIncident;

class IncidentArchive extends Component
{
    use WithPagination;

    public int    $clientId;
    public string $search         = '';
    public string $severityFilter = '';
    public string $statusFilter   = '';
    public ?int   $selectedId     = null;

    public function mount(int $clientId): void
    {
        $this->clientId = $clientId;
    }

    public function selectIncident(int $id): void
    {
        $this->selectedId = $id;
    }

    public function clearSelected(): void
    {
        $this->selectedId = null;
    }

    public function render()
    {
        $incidents = AfisIncident::where('client_id', $this->clientId)
            ->with(['tracker', 'aiReport', 'loggedBy'])
            ->when($this->search, fn($q) => $q->where(function ($q) {
                $q->where('description', 'like', "%{$this->search}%")
                  ->orWhere('vehicle_label', 'like', "%{$this->search}%");
            }))
            ->when($this->severityFilter, fn($q) => $q->where('severity', $this->severityFilter))
            ->when($this->statusFilter,   fn($q) => $q->where('status',   $this->statusFilter))
            ->latest('incident_date')
            ->paginate(15);

        $selected = $this->selectedId
            ? AfisIncident::with(['tracker', 'aiReport', 'loggedBy'])->find($this->selectedId)
            : null;

        return view('afisincidents::livewire.incident-archive', compact('incidents', 'selected'));
    }
}