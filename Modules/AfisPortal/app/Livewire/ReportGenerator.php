<?php

namespace Modules\AfisPortal\Livewire;

use Carbon\Carbon;
use Livewire\Component;
use Modules\AdmmInventory\Models\Client;
use Modules\AfisPipeline\Models\AfisTrackerGroup;
use Modules\AfisPortal\Services\ReportDataService;

class ReportGenerator extends Component
{
    public ?int    $clientId   = null;
    public ?int    $groupId    = null;
    public string  $period     = 'monthly';
    public string  $fromDate   = '';
    public string  $toDate     = '';
    public string  $month      = '';
    public string  $error      = '';
    public bool    $isAdmin    = true;

    public function mount(bool $isAdmin = true, ?int $clientId = null): void
    {
        $this->isAdmin  = $isAdmin;
        $this->clientId = $clientId;
        $this->month    = Carbon::now()->subMonth()->format('Y-m');
        $this->fromDate = Carbon::now()->subMonth()->startOfMonth()->format('Y-m-d');
        $this->toDate   = Carbon::now()->subMonth()->endOfMonth()->format('Y-m-d');
    }

    public function updatedPeriod(): void
    {
        match($this->period) {
            'daily'   => $this->fromDate = $this->toDate = Carbon::yesterday()->format('Y-m-d'),
            'monthly' => $this->setMonthDates(),
            default   => null,
        };
    }

    public function updatedMonth(): void
    {
        $this->setMonthDates();
    }

    private function setMonthDates(): void
    {
        if ($this->month) {
            $this->fromDate = Carbon::parse($this->month)->startOfMonth()->format('Y-m-d');
            $this->toDate   = Carbon::parse($this->month)->endOfMonth()->format('Y-m-d');
        }
    }

    public function generateStandardReport(): mixed
    {
        $this->error = '';

        if (!$this->clientId) {
            $this->error = 'Please select a client.';
            return null;
        }

        if ($this->isAdmin) {
            return redirect()->route('admin.afis.reports.standard', [
                'clientId' => $this->clientId,
                'from'     => $this->fromDate,
                'to'       => $this->toDate,
                'groupId'  => $this->groupId,
            ]);
        }

        return redirect()->route('client.reports.generate.standard', [
            'from'    => $this->fromDate,
            'to'      => $this->toDate,
            'groupId' => $this->groupId,
        ]);
    }

    public function generateAiReport(): mixed
    {
        $this->error = '';

        if (!$this->clientId) {
            $this->error = 'Please select a client.';
            return null;
        }

        if ($this->isAdmin) {
            return redirect()->route('admin.afis.reports.ai', [
                'clientId' => $this->clientId,
                'from'     => $this->fromDate,
                'to'       => $this->toDate,
                'groupId'  => $this->groupId,
            ]);
        }

        return redirect()->route('client.reports.generate.ai', [
            'from'    => $this->fromDate,
            'to'      => $this->toDate,
            'groupId' => $this->groupId,
        ]);
    }

    public function deleteReport(int $id): void
    {
        $report = \Modules\AfisPortal\Models\AfisGeneratedReport::findOrFail($id);
        \Illuminate\Support\Facades\Storage::disk('local')->delete($report->file_path);
        $report->delete();
        session()->flash('success', 'Report deleted.');
    }

    public function render()
    {
        $clients = $this->isAdmin
            ? Client::active()->orderBy('name')->get()
            : collect();

        $groups = $this->clientId
        ? AfisTrackerGroup::where('client_id', $this->clientId)->orderBy('title')->get()
        : collect();

        $recentReports = \Modules\AfisPortal\Models\AfisGeneratedReport::with('client', 'generatedBy')
            ->where('client_id', $this->clientId)
            ->latest()
            ->limit(20)
            ->get();

        return view('afisportal::livewire.report-generator', compact('clients', 'groups', 'recentReports'));
    }
}