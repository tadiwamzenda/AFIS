<?php

namespace Modules\AfisPortal\Livewire;

use Carbon\Carbon;
use Livewire\Component;
use Modules\AdmmInventory\Models\Client;
use Modules\AfisPipeline\Models\AfisTrackerGroup;
use Modules\AfisPortal\Services\ReportDataService;

class ReportGenerator extends Component
{
    public ?int    $clientId      = null;
    public array   $selectedGroups = []; // multiple group IDs
    public string  $period        = 'monthly';
    public string  $fromDate      = '';
    public string  $toDate        = '';
    public string  $month         = '';
    public string  $error         = '';
    public bool    $isAdmin       = true;
    public bool    $largeFleet    = false;

    public function updatedClientId(): void
    {
        $this->selectedGroups = [];
        $trackerCount = \Modules\AfisPipeline\Models\AfisTracker::where('client_id', $this->clientId)->count();
        $this->largeFleet = $trackerCount > 200;
    }

    public function toggleGroup(int $groupId): void
    {
        if (in_array($groupId, $this->selectedGroups)) {
            $this->selectedGroups = array_values(array_filter(
                $this->selectedGroups, fn($id) => $id !== $groupId
            ));
        } else {
            $this->selectedGroups[] = $groupId;
        }
    }

    public function selectAllGroups(array $groupIds): void
    {
        foreach ($groupIds as $id) {
            if (!in_array($id, $this->selectedGroups)) {
                $this->selectedGroups[] = $id;
            }
        }
    }

    public function deselectAllGroups(array $groupIds): void
    {
        $this->selectedGroups = array_values(array_filter(
            $this->selectedGroups, fn($id) => !in_array($id, $groupIds)
        ));
    }

    public function toggleParent(string $parent, array $groupIds): void
    {
        $allSelected = collect($groupIds)->every(fn($id) => in_array($id, $this->selectedGroups));

        if ($allSelected) {
            $this->selectedGroups = array_values(array_filter(
                $this->selectedGroups,
                fn($id) => !in_array($id, $groupIds)
            ));
        } else {
            foreach ($groupIds as $id) {
                if (!in_array($id, $this->selectedGroups)) {
                    $this->selectedGroups[] = $id;
                }
            }
        }
    }

    private function getParentRegion(string $title): string
    {
        $parts = explode(' ', trim($title));
        // Special case: ZETDC TR → use 3 words (ZETDC TR EAST / ZETDC TR WEST)
        if (count($parts) >= 3 && strtoupper($parts[0]) === 'ZETDC' && strtoupper($parts[1]) === 'TR') {
            return implode(' ', array_slice($parts, 0, 3));
        }
        return implode(' ', array_slice($parts, 0, 2));
    }

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
                'clientId'      => $this->clientId,
                'from'          => $this->fromDate,
                'to'            => $this->toDate,
                'selectedGroups'=> $this->selectedGroups,
            ]);
        }

        return redirect()->route('client.reports.generate.standard', [
            'from'           => $this->fromDate,
            'to'             => $this->toDate,
            'selectedGroups' => $this->selectedGroups,
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
                'clientId'       => $this->clientId,
                'from'           => $this->fromDate,
                'to'             => $this->toDate,
                'selectedGroups' => $this->selectedGroups,
            ]);
        }

        return redirect()->route('client.reports.generate.ai', [
            'from'           => $this->fromDate,
            'to'             => $this->toDate,
            'selectedGroups' => $this->selectedGroups,
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

        $groups       = collect();
        $parentGroups = collect();

        if ($this->clientId) {
            $allGroups = AfisTrackerGroup::where('client_id', $this->clientId)
                ->orderBy('title')
                ->get();

            if ($this->largeFleet) {
                // Build parent group map
                $parentMap = [];
                foreach ($allGroups as $group) {
                    $parent = $this->getParentRegion($group->title);
                    $parentMap[$parent][] = $group->navixy_group_id;
                }
                ksort($parentMap);
                $parentGroups = collect($parentMap);
            } else {
                $groups = $allGroups;
            }
        }

        $recentReports = \Modules\AfisPortal\Models\AfisGeneratedReport::with('client', 'generatedBy')
            ->when($this->clientId, fn($q) => $q->where('client_id', $this->clientId))
            ->latest()
            ->limit(20)
            ->get();

        return view('afisportal::livewire.report-generator', compact(
            'clients', 'groups', 'parentGroups', 'recentReports'
        ));
    }

}