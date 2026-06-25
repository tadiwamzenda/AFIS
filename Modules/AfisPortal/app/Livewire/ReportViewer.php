<?php

namespace Modules\AfisPortal\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Modules\AdmmInventory\Models\Client;
use Modules\AfisEngine\Models\AfisAiReport;

class ReportViewer extends Component
{
    use WithPagination;

    public int     $clientId;
    public ?int    $selectedReportId = null;
    public string  $typeFilter       = '';

    public function mount(int $clientId): void
    {
        $this->clientId = $clientId;
    }

    public function selectReport(int $id): void
    {
        $this->selectedReportId = $id;
    }

    public function clearReport(): void
    {
        $this->selectedReportId = null;
    }

    public function render()
    {
        $client = Client::findOrFail($this->clientId);

        $reports = AfisAiReport::where('client_id', $this->clientId)
            ->completed()
            ->when($this->typeFilter, fn($q) => $q->where('report_type', $this->typeFilter))
            ->latest()
            ->paginate(15);

        $selectedReport = $this->selectedReportId
            ? AfisAiReport::find($this->selectedReportId)
            : null;

        return view('afisportal::livewire.report-viewer', compact(
            'client', 'reports', 'selectedReport'
        ));
    }
}