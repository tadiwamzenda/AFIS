<?php

namespace Modules\AdmmInventory\Http\Controllers;

use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Modules\AdmmInventory\Models\StockSnapshot;
use Modules\AdmmInventory\Services\StockSnapshotService;

class StockManagementController extends Controller
{
    public function exportPdf()
    {
        $service    = app(StockSnapshotService::class);
        $liveCounts = $service->getLiveCounts();
        $weekly     = $this->getWeeklyTrend();
        $monthly    = $this->getMonthlyTrend();

        $pdf = Pdf::loadView('admminventory::stock-management.pdf', compact(
            'liveCounts', 'weekly', 'monthly'
        ))
        ->setPaper('a4', 'landscape')
        ->setOptions([
            'defaultFont'          => 'sans-serif',
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled'      => false,
        ]);

        return response($pdf->output())
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="asset-stock-report-' . now()->format('Y-m-d') . '.pdf"');
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
            $monthEnd = Carbon::now()->subMonths($i)->endOfMonth();
            $label    = $monthEnd->format('M Y');

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
}