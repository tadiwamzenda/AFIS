<?php

namespace Modules\AdmmInventory\Livewire\StockManagement;

use Carbon\Carbon;
use Livewire\Component;
use Barryvdh\DomPDF\Facade\Pdf;
use Modules\AdmmInventory\Models\StockSnapshot;
use Modules\AdmmInventory\Services\StockSnapshotService;

class StockManagement extends Component
{
    public string $trendView = 'weekly'; // weekly | monthly

    public function switchTrend(string $view): void
    {
        $this->trendView = $view;
    }

    public function downloadPdf()
    {
        $service    = app(StockSnapshotService::class);
        $liveCounts = $service->getLiveCounts();
        $weekly     = $this->getWeeklyTrend();
        $monthly    = $this->getMonthlyTrend();

        $pdf = Pdf::loadView('admminventory::stock-management.pdf', compact(
            'liveCounts', 'weekly', 'monthly'
        ))->setPaper('a4', 'landscape');

        return response()->streamDownload(
            fn() => print($pdf->output()),
            'asset-stock-report-' . now()->format('Y-m-d') . '.pdf',
            ['Content-Type' => 'application/pdf']
        );
    }

    private function getWeeklyTrend(): array
    {
        $weeks = [];
        for ($i = 11; $i >= 0; $i--) {
            $weekEnd   = Carbon::now()->subWeeks($i)->endOfWeek();
            $weekStart = $weekEnd->copy()->startOfWeek();
            $label     = $weekStart->format('d M') . ' - ' . $weekEnd->format('d M');

            $snapshots = StockSnapshot::where('snapshot_date', '<=', $weekEnd->toDateString())
                ->whereIn('snapshot_date', function($q) use ($weekEnd) {
                    $q->selectRaw('MAX(snapshot_date)')
                        ->from('adm_stock_snapshots')
                        ->where('snapshot_date', '<=', $weekEnd->toDateString())
                        ->groupBy('parent_client');
                })
                ->get()
                ->groupBy('parent_client');

            $weeks[$label] = $snapshots;
        }
        return $weeks;
    }

    private function getMonthlyTrend(): array
    {
        $months = [];
        for ($i = 11; $i >= 0; $i--) {
            $monthEnd   = Carbon::now()->subMonths($i)->endOfMonth();
            $label      = $monthEnd->format('M Y');

            $snapshots = StockSnapshot::where('snapshot_date', '<=', $monthEnd->toDateString())
                ->whereIn('snapshot_date', function($q) use ($monthEnd) {
                    $q->selectRaw('MAX(snapshot_date)')
                        ->from('adm_stock_snapshots')
                        ->where('snapshot_date', '<=', $monthEnd->toDateString())
                        ->groupBy('parent_client');
                })
                ->get()
                ->groupBy('parent_client');

            $months[$label] = $snapshots;
        }
        return $months;
    }

    public function render()
    {
        $service    = app(StockSnapshotService::class);
        $liveCounts = $service->getLiveCounts();

        $trend = $this->trendView === 'weekly'
            ? $this->getWeeklyTrend()
            : $this->getMonthlyTrend();

        // Get unique client names across all snapshots
        $clients = collect($liveCounts)->pluck('client')->toArray();

        return view('admminventory::livewire.stock-management.index', compact(
            'liveCounts', 'trend', 'clients'
        ));
    }
}