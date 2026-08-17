<?php

namespace Modules\AfisIncidents\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Modules\AfisIncidents\Models\AfisIncident;

class IncidentArchive extends Component
{
    use WithPagination;

    public int    $clientId;
    public bool   $isAdmin        = true;
    public string $search         = '';
    public string $severityFilter = '';
    public string $statusFilter   = '';
    public ?int   $selectedId     = null;

    public function mount(int $clientId, bool $isAdmin = true): void
    {
        $this->clientId = $clientId;
        $this->isAdmin  = $isAdmin;
    }

    public function selectIncident(int $id): void
    {
        $this->selectedId = $id;
    }

    public function clearSelected(): void
    {
        $this->selectedId = null;
    }

    public function deleteIncident(int $id): void
    {
        $incident = AfisIncident::findOrFail($id);

        if ($incident->report_path) {
            \Illuminate\Support\Facades\Storage::disk('local')->delete($incident->report_path);
        }

        $incident->delete();
        session()->flash('success', 'Incident deleted.');
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
            // Clients only see incidents THEY personally logged — not other
            // users at the same client company. Admin/Staff unrestricted.
            ->when(!$this->isAdmin, fn($q) => $q->where('logged_by', \Illuminate\Support\Facades\Auth::id()))
            ->latest('created_at')
            ->paginate(15);

        $selected = $this->selectedId
            ? AfisIncident::with(['tracker', 'aiReport', 'loggedBy'])
                ->when(!$this->isAdmin, fn($q) => $q->where('logged_by', \Illuminate\Support\Facades\Auth::id()))
                ->find($this->selectedId)
            : null;

        return view('afisincidents::livewire.incident-archive', compact('incidents', 'selected'));
    }
}