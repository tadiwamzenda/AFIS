<?php

namespace Modules\AdmmDocuments\Imports;

use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Collection;
use Modules\AdmmInventory\Models\Client;
use Modules\AdmmInventory\Models\GpsDevice;
use Modules\AdmmInventory\Models\SimCard;

class GpsDeviceImport implements ToCollection, WithHeadingRow
{
    public int   $imported = 0;
    public int   $skipped  = 0;
    public array $errors   = [];

    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            $rowNum = $index + 2;

            $imei = trim($row['gps_device_imei'] ?? $row['imei'] ?? '');

            if (empty($imei)) {
                $this->skipped++;
                continue;
            }

            // Find client by name
            $clientName = trim($row['client'] ?? '');
            $client     = $clientName
                ? Client::where('name', 'like', "%{$clientName}%")->first()
                : null;

            // Find SIM by MSISDN
            $msisdn = trim($row['sim_msisdn'] ?? $row['msisdn'] ?? '');
            $sim    = $msisdn
                ? SimCard::where('msisdn', 'like', "%{$msisdn}%")->first()
                : null;

            // Parse installation date
            $installedAt = null;
            $rawDate     = $row['date_of_installation'] ?? $row['installation_date'] ?? null;
            if ($rawDate) {
                try {
                    $installedAt = is_numeric($rawDate)
                        ? \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($rawDate)->format('Y-m-d')
                        : \Carbon\Carbon::parse($rawDate)->format('Y-m-d');
                } catch (\Throwable $e) {
                    $installedAt = null;
                }
            }

            try {
                GpsDevice::updateOrCreate(
                    ['imei' => $imei],
                    [
                        'device_type'          => trim($row['gps_device_type'] ?? $row['device_type'] ?? ''),
                        'vehicle_registration' => strtoupper(trim($row['vehicle_id'] ?? $row['vehicle_registration'] ?? '')),
                        'vehicle_make'         => trim($row['vehicle_make'] ?? ''),
                        'fleet_number'         => trim($row['fleet_no'] ?? $row['fleet_number'] ?? ''),
                        'technician'           => trim($row['bantu_technician'] ?? $row['technician'] ?? ''),
                        'status'               => $client ? 'installed' : 'in_office_stock',
                        'client_id'            => $client?->id,
                        'sim_card_id'          => $sim?->id,
                        'installed_at'         => $installedAt,
                        'notes'                => trim($row['notes'] ?? ''),
                    ]
                );
                $this->imported++;
            } catch (\Throwable $e) {
                $this->errors[] = "Row {$rowNum} (IMEI: {$imei}): " . $e->getMessage();
                $this->skipped++;
            }
        }
    }
}