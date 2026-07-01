<?php

namespace Modules\AdmmDocuments\Imports;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Modules\AdmmInventory\Models\AssetRecord;

class AssetRegisterImport implements ToCollection, WithHeadingRow
{
    public int   $imported = 0;
    public int   $skipped  = 0;
    public array $errors   = [];

    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            $rowNum = $index + 2;

            // Skip completely empty rows
            $rowValues = $row->filter(fn($v) => !empty(trim((string)$v)));
            if ($rowValues->isEmpty()) {
                $this->skipped++;
                continue;
            }

            // Parse date
            $rawDate     = $row['installation_date'] ?? null;
            $installedAt = null;
            if ($rawDate) {
                try {
                    $installedAt = is_numeric($rawDate)
                        ? \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float)$rawDate)->format('Y-m-d')
                        : Carbon::parse($rawDate)->format('Y-m-d');
                } catch (\Throwable) {
                    $installedAt = null;
                }
            }

            // Normalize ISP
            $isp = strtoupper(trim($row['sim_card_isp'] ?? ''));
            $isp = match(true) {
                str_contains($isp, 'ECONET')  => 'ECONET',
                str_contains($isp, 'NETONE')  => 'NETONE',
                str_contains($isp, 'GLOBAL')  => 'GLOBAL',
                default                        => null,
            };

            // Normalize SIM type
            $simType = strtoupper(trim($row['sim_card_type'] ?? ''));
            $simType = in_array($simType, ['MULTIMEDIA', 'TELEMETRY']) ? $simType : null;

            // Normalize location
            $location = strtoupper(trim($row['location'] ?? 'STOCK'));
            $location = in_array($location, ['CLIENT', 'STOCK', 'LOST']) ? $location : 'STOCK';

            // Normalize device state
            $deviceState = strtoupper(trim($row['gps_device_state'] ?? 'ACTIVE'));
            $deviceState = in_array($deviceState, ['ACTIVE', 'INACTIVE', 'MALFUNCTION']) ? $deviceState : 'ACTIVE';

            $imei = trim($row['gps_device_imei'] ?? '');

            try {
                $data = [
                    'installation_date' => $installedAt,
                    'client'            => trim($row['client'] ?? '') ?: null,
                    'vehicle_reg_no'    => strtoupper(trim($row['vehicle_reg_no'] ?? '')) ?: null,
                    'vehicle_fleet_no'  => trim($row['vehicle_fleet_no'] ?? '') ?: null,
                    'vehicle_make'      => trim($row['vehicle_make'] ?? '') ?: null,
                    'gps_device_imei'   => $imei ?: null,
                    'gps_device_name'   => trim($row['gps_device_name'] ?? '') ?: null,
                    'gps_device_type'   => trim($row['gps_device_type'] ?? '') ?: null,
                    'configuration'     => trim($row['configuration'] ?? '') ?: null,
                    'gps_device_state'  => $deviceState,
                    'sim_card_serial_no'=> trim($row['sim_card_serial_no'] ?? '') ?: null,
                    'sim_card_phone_no' => trim($row['sim_card_phone_no'] ?? '') ?: null,
                    'sim_card_type'     => $simType,
                    'sim_card_isp'      => $isp,
                    'location'          => $location,
                    'technician'        => trim($row['technician_installer'] ?? $row['technician'] ?? '') ?: null,
                    'comment'           => trim($row['comment'] ?? '') ?: null,
                    'client_name'       => trim($row['client_name'] ?? '') ?: null,
                    'client_contact'    => trim($row['client_contact'] ?? '') ?: null,
                    'client_email'      => trim($row['client_email'] ?? '') ?: null,
                ];

                if ($imei) {
                    AssetRecord::updateOrCreate(['gps_device_imei' => $imei], $data);
                } else {
                    AssetRecord::create($data);
                }

                $this->imported++;
            } catch (\Throwable $e) {
                $this->errors[] = "Row {$rowNum}: " . $e->getMessage();
                $this->skipped++;
            }
        }
    }
}