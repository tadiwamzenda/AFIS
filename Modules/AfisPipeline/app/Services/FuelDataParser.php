<?php

namespace Modules\AfisPipeline\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Modules\AfisPipeline\Models\AfisFuelDaily;
use Modules\AfisPipeline\Models\AfisTracker;
use Modules\AdmmInventory\Models\Client;

class FuelDataParser
{
    /**
     * Generate Navixy plugin 95 fuel report, parse it and store in afis_fuel_daily.
     * Returns number of records stored.
     */
    public function syncFuelData(
        Client           $client,
        array            $trackerIds,
        Carbon           $from,
        Carbon           $to,
        int              $instance,
        NavixyDataService $navixy
    ): int {
        if (empty($trackerIds)) return 0;

        // Step 1 — Generate report
        $reportId = $navixy->generateNavixyFuelReport($trackerIds, $from, $to, $instance);
        if (!$reportId) return 0;

        // Step 2 — Wait for Navixy to generate (poll up to 60 seconds)
        $excelContent = null;
        $attempts     = 0;
        while ($attempts < 6) {
            sleep(10);
            $content = $navixy->downloadNavixyFuelReport($reportId, $instance);
            // Check if response is actually Excel (not an error JSON)
            if ($content && strlen($content) > 100 && substr($content, 0, 2) === 'PK') {
                $excelContent = $content;
                break;
            }
            $attempts++;
        }

        if (!$excelContent) {
            Log::warning('FuelDataParser: could not download fuel report', [
                'client'    => $client->name,
                'report_id' => $reportId,
            ]);
            return 0;
        }

        // Step 3 — Parse Excel
        return $this->parseAndStore($excelContent, $client, $instance);
    }

    private function parseAndStore(string $content, Client $client, int $instance): int
{
    $tmpFile = tempnam(sys_get_temp_dir(), 'fuel_') . '.xlsx';
    file_put_contents($tmpFile, $content);

    try {
        $sheets = \Maatwebsite\Excel\Facades\Excel::toArray([], $tmpFile);
    } catch (\Throwable $e) {
        Log::warning('FuelDataParser: Excel parse error', ['error' => $e->getMessage()]);
        unlink($tmpFile);
        return 0;
    }

    unlink($tmpFile);
    if (empty($sheets)) return 0;

    $stored       = 0;
    $vehicleLabel = null;

    foreach ($sheets as $sheet) {
        if (empty($sheet)) continue;

        $firstCell = trim($sheet[0][0] ?? '');

        // Vehicle sheet — extract label from "Test Fuel: AFJ 5349"
        if (str_contains($firstCell, ':') && !str_starts_with($firstCell, 'Details') && !str_starts_with($firstCell, 'Date') && !str_starts_with($firstCell, 'For the period')) {
        // Extract vehicle label — Navixy appends ": VEHICLE_LABEL" to report title
        $parts = explode(':', $firstCell, 2);
        $vehicleLabel = trim($parts[1] ?? '');
            continue;
        }

        // Details by dates sheet
        if ($firstCell === 'Details by dates' && $vehicleLabel) {
            // Find the tracker
            $tracker = AfisTracker::where('client_id', $client->id)
                ->where('label', $vehicleLabel)
                ->first();

            if (!$tracker) {
                // Try partial match
                $tracker = AfisTracker::where('client_id', $client->id)
                    ->where('label', 'like', "%{$vehicleLabel}%")
                    ->first();
            }

            if (!$tracker) {
                $vehicleLabel = null;
                continue;
            }

            // Find header row — "Date" in first column
            $dataStart = null;
            foreach ($sheet as $i => $row) {
                if (($row[0] ?? '') === 'Date') {
                    $dataStart = $i + 1;
                    break;
                }
            }

            if ($dataStart === null) {
                $vehicleLabel = null;
                continue;
            }

            // Parse data rows
            for ($i = $dataStart; $i < count($sheet); $i++) {
                $row = $sheet[$i];
                $dateVal = $row[0] ?? null;

                if (!$dateVal || $dateVal === 'In total:' || is_null($dateVal)) continue;

                // Parse date — format is "01.06.2026"
                try {
                    $date = Carbon::createFromFormat('d.m.Y', $dateVal)->format('Y-m-d');
                } catch (\Throwable) {
                    continue;
                }

                $mileage    = (float) ($row[1] ?? 0);
                $refuels    = (int)   ($row[2] ?? 0);
                $volume     = (float) ($row[3] ?? 0);
                $consumed   = (float) ($row[4] ?? 0);
                $l100km     = (float) ($row[5] ?? 0); // L/100km
                
                // Convert L/100km to km/L
                $kmPerLitre = $l100km > 0 ? round(100 / $l100km, 4) : null;

                // Skip rows where everything is zero
                if ($mileage == 0 && $refuels == 0 && $consumed == 0) continue;

                AfisFuelDaily::updateOrCreate(
                    ['tracker_id' => $tracker->id, 'date' => $date],
                    [
                        'client_id'                => $client->id,
                        'navixy_tracker_id'        => $tracker->navixy_tracker_id,
                        'vehicle_label'            => $vehicleLabel,
                        'mileage_km'               => $mileage,
                        'refuel_count'             => $refuels,
                        'volume_litres'            => $volume > 0 ? $volume : null,
                        'consumed_litres'          => $consumed > 0 ? $consumed : null,
                        'consumption_km_per_litre' => $kmPerLitre,
                        'has_drain'                => false,
                        'drain_litres'             => null,
                    ]
                );
                $stored++;
            }

            $vehicleLabel = null; // Reset for next pair
        }
    }

    return $stored;
}
}