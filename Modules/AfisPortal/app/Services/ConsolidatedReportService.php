<?php

namespace Modules\AfisPortal\Services;

use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Modules\AdmmInventory\Models\Client;
use Modules\AfisPipeline\Models\AfisTrackerGroup;

class ConsolidatedReportService
{
    public function __construct(
        private ReportDataService $reportData
    ) {}

    public function generate(
        Client $client,
        Carbon $from,
        Carbon $to,
        array  $selectedGroups = []
    ): string {
        ini_set('memory_limit', '2048M');
        set_time_limit(0);

        // ── Step 1: Build summary for entire fleet ────────────────────────────
        $summaryData = $this->reportData->buildReportData(
            $client, $from, $to, $selectedGroups
        );

        // ── Step 2: Group vehicles by parent region (first 2 words of group) ──
        $getParentRegion = fn($title) => implode(' ', array_slice(explode(' ', trim($title)), 0, 2));

        $vehiclesByParent = collect($summaryData['vehicles'])
            ->groupBy(fn($v) => $getParentRegion($v['group']));

        $regionSections = [];

        foreach ($vehiclesByParent as $parentRegion => $regionVehicles) {
            $regionVehicles = $regionVehicles->values()->toArray();

            if (empty($regionVehicles)) continue;

            $regionLabels   = collect($regionVehicles)->pluck('label')->toArray();

            $afterHrsVehicles = array_values(array_filter($regionVehicles, fn($v) => $v['after_hrs_km'] > 0));
            $speedingVehicles = array_values(array_filter($regionVehicles, fn($v) => $v['speeding_trips'] > 0));
            $weekendVehicles  = array_values(array_filter($regionVehicles, fn($v) => ($v['weekend_total'] ?? 0) > 0));

            $fuelVehicles = array_values(array_filter(
                $summaryData['fuel_flat'] ?? [],
                fn($f) => in_array($f['label'], $regionLabels)
            ));

            $speedingDetail = array_values(array_filter(
                $summaryData['speeding_detail'] ?? [],
                fn($s) => in_array($s['label'], $regionLabels)
            ));

            $regionSections[] = [
                'region'          => $parentRegion,
                'fleet_size'      => count($regionVehicles),
                'total_mileage'   => round(collect($regionVehicles)->sum('mileage'), 2),
                'weekend_km'      => round(collect($regionVehicles)->sum('weekend_km'), 2),
                'after_hrs_km'    => round(collect($regionVehicles)->sum('after_hrs_km'), 2),
                'vehicles'        => $regionVehicles,
                'after_hrs'       => $afterHrsVehicles,
                'speeding'        => $speedingVehicles,
                'speeding_detail' => $speedingDetail,
                'weekend'         => $weekendVehicles,
                'fuel_flat'       => $fuelVehicles,
                'weekend_dates'   => $summaryData['weekend_dates'] ?? [],
            ];

            gc_collect_cycles();
        }

        // ── Step 3: Render single PDF ─────────────────────────────────────────
        $pdf = Pdf::loadView('afisportal::reports.consolidated', [
            'client'       => $client,
            'from'         => $from,
            'to'           => $to,
            'summary'      => $summaryData,
            'regions'      => $regionSections,
            'generated_at' => Carbon::now(),
        ])
        ->setPaper('a4', 'landscape')
        ->setOptions([
            'defaultFont'          => 'sans-serif',
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled'      => false,
            'dpi'                  => 96,
        ]);

        return $pdf->output();
    }
}