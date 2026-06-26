<?php

namespace Modules\AfisIntelligence\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Modules\AfisEngine\Models\AfisAiReport;

class IntelligenceArchive extends Component
{
    use WithPagination;

    public int    $clientId;
    public string $typeFilter = '';
    public ?int   $selectedId = null;

    public function mount(int $clientId): void
    {
        $this->clientId = $clientId;
    }

    public function selectReport(int $id): void
    {
        $this->selectedId = $id;
    }

    public function clearSelected(): void
    {
        $this->selectedId = null;
    }

    public function render()
    {
        $reports = AfisAiReport::where('client_id', $this->clientId)
            ->whereIn('report_type', ['fleet_intelligence', 'predictive_intelligence'])
            ->completed()
            ->when($this->typeFilter, fn($q) => $q->where('report_type', $this->typeFilter))
            ->latest()
            ->paginate(15);

        $selected = $this->selectedId
            ? AfisAiReport::find($this->selectedId)
            : null;

        return view('afisintelligence::livewire.intelligence-archive', compact('reports', 'selected'));
    }
}