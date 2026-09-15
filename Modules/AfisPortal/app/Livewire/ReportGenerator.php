<?php

namespace Modules\AfisPortal\Livewire;

use Carbon\Carbon;
use Livewire\Component;
use Modules\AdmmInventory\Models\Client;
use Modules\AfisPipeline\Models\AfisTrackerGroup;
use Modules\AfisPortal\Services\ReportDataService;
use Illuminate\Support\Facades\Http;

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
            'weekly'  => [
                $this->fromDate = Carbon::now()->subWeek()->startOfWeek()->format('Y-m-d'),
                $this->toDate   = Carbon::now()->subWeek()->endOfWeek()->format('Y-m-d'),
            ],
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

        try {
            $rangeDays = \Carbon\Carbon::parse($this->fromDate)->startOfDay()->diffInDays(\Carbon\Carbon::parse($this->toDate)->startOfDay()) + 1;
        } catch (\Throwable $e) {
            
            \Illuminate\Support\Facades\Log::error('ReportGenerator: invalid date range', [
                'fromDate' => $this->fromDate,
                'toDate'   => $this->toDate,
                'error'    => $e->getMessage(),
            ]);
            $this->error = 'Invalid date range selected. Please re-select the period and try again.';
            return null;
        }

        if ($rangeDays > 31) {
            $this->error = "Standard Reports are limited to 31 days at a time (you selected {$rangeDays}). For longer periods, please generate multiple reports covering shorter ranges.";
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
        // Derive allowed group IDs from trackers the user can see in Navixy
        // (group/list doesn't respect per-user security restrictions — tracker/list does)
        $allowedNavixyGroupIds = null;
        if (!\Illuminate\Support\Facades\Auth::user()?->isBtStaff()) {
            $sessionHash = session('navixy_hash');
            if ($sessionHash) {
                try {
                    $response = \Illuminate\Support\Facades\Http::timeout(15)
                        ->withHeaders(['Content-Type' => 'application/json'])
                        ->post('https://api.us.navixy.com/v2/tracker/list', [
                            'hash' => $sessionHash,
                        ]);
                    // Extract unique group_ids from visible trackers
                    $allowedNavixyGroupIds = collect($response->json()['list'] ?? [])
                        ->pluck('group_id')
                        ->filter()
                        ->unique()
                        ->values()
                        ->toArray();
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('ReportGenerator: could not fetch Navixy trackers for group filter', [
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        $allGroups = AfisTrackerGroup::where('client_id', $this->clientId)
            ->when($allowedNavixyGroupIds !== null, fn($q) => $q->whereIn('navixy_group_id', $allowedNavixyGroupIds))
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
                // Merge groups sharing an EXACT identical title into one
                // entry. Confirmed via direct data inspection: a client
                // migrated from a Bantu Track master-account sub-user to
                // their own independent Navixy account keeps BOTH the old
                // and new navixy_group_id as separate rows with the same
                // title (REF: "REF HEAD OFFICE" and "REF MASVINGO" each
                // exist twice). This never touches trackers' real
                // navixy_group_id or deletes any AfisTrackerGroup row —
                // purely a selection-layer merge, reusing the exact same
                // [title => group_ids[]] shape (and toggleParent()) already
                // built for the large-fleet branch above.
                $groupMap = [];
                foreach ($allGroups as $group) {
                    $groupMap[$group->title][] = $group->navixy_group_id;
                }
                ksort($groupMap);
                $groups = collect($groupMap);

                $groupVehicleCounts = $groups->mapWithKeys(function ($groupIds, $title) {
                    $count = \Modules\AfisPipeline\Models\AfisTracker::whereIn('navixy_group_id', $groupIds)->count();
                    return [$title => $count];
                });
            }
        }

        $recentReports = \Modules\AfisPortal\Models\AfisGeneratedReport::with('client', 'generatedBy')
            ->when($this->clientId, fn($q) => $q->where('client_id', $this->clientId))
            // Clients only see reports THEY personally generated — not other
            // users at the same client company. Admin/Staff are unrestricted,
            // same as before.
            ->when(!$this->isAdmin, fn($q) => $q->where('generated_by', \Illuminate\Support\Facades\Auth::id()))
            ->latest()
            ->limit(20)
            ->get();

        return view('afisportal::livewire.report-generator', compact(
            'clients', 'groups', 'parentGroups', 'recentReports'
        ))->with('groupVehicleCounts', $groupVehicleCounts ?? collect());
    }

}