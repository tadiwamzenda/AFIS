<?php

namespace Modules\AdmmDocuments\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Modules\AdmmDocuments\Models\SimImport;
use Modules\AdmmInventory\Models\SimCard;

class SimCardImportService
{
    /**
     * Expected headers in the upload template.
     */
    const HEADERS = ['SIM Card Number', 'ISP Provider', 'Batch Code', 'Type'];

    /**
     * Parse the uploaded Excel and return mapped rows.
     */
    public function parse(string $path): Collection
{
    // Use PhpSpreadsheet directly — more reliable than the Excel facade wrapper
    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($path);
    $worksheet   = $spreadsheet->getActiveSheet();
    $rows        = $worksheet->toArray(null, true, true, false);

    if (empty($rows) || count($rows) < 2) {
        return collect();
    }

    // First row is headers
    $headers = array_map('trim', $rows[0]);

    // Validate required columns exist
    foreach (self::HEADERS as $required) {
        if (!in_array($required, $headers)) {
            throw new \RuntimeException(
                "Missing required column \"{$required}\". Expected: " . implode(', ', self::HEADERS)
            );
        }
    }

    // Map remaining rows
    return collect(array_slice($rows, 1))
        ->filter(fn($row) => array_filter($row, fn($v) => $v !== null && trim((string) $v) !== ''))
        ->map(function ($row) use ($headers) {
            $data = array_combine($headers, $row);
            return [
                'msisdn'           => $this->formatMsisdn($data['SIM Card Number'] ?? ''),
                'network_provider' => trim($data['ISP Provider'] ?? ''),
                'batch_code'       => trim($data['Batch Code'] ?? '') ?: null,
                'bundle_type'      => trim($data['Type'] ?? '') ?: null,
            ];
        })
        ->filter(fn($row) => !empty($row['msisdn']) && !empty($row['network_provider']))
        ->values();
}

    /**
     * Import parsed rows — update existing SIMs, create new ones.
     */
    public function import(Collection $rows, string $filename): SimImport
    {
        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            try {
                $existing = SimCard::where('msisdn', $row['msisdn'])->first();

                if ($existing) {
                    $existing->update([
                        'network_provider' => $row['network_provider'],
                        'batch_code'       => $row['batch_code'],
                        'bundle_type'      => $row['bundle_type'],
                    ]);
                    $updated++;
                } else {
                    SimCard::create([
                        'iccid'            => 'IMP-' . preg_replace('/\D/', '', $row['msisdn']),
                        'msisdn'           => $row['msisdn'],
                        'network_provider' => $row['network_provider'],
                        'batch_code'       => $row['batch_code'],
                        'bundle_type'      => $row['bundle_type'],
                        'status'           => 'unassigned',
                        'location_context' => 'unallocated',
                    ]);
                    $created++;
                }
            } catch (\Throwable $e) {
                Log::warning('SimImport: skipped row', ['row' => $row, 'error' => $e->getMessage()]);
                $skipped++;
            }
        }

        return SimImport::create([
            'provider'      => 'mixed',
            'filename'      => $filename,
            'total_rows'    => $rows->count(),
            'created_count' => $created,
            'updated_count' => $updated,
            'skipped_count' => $skipped,
            'imported_by'   => Auth::id(),
        ]);
    }

    private function formatMsisdn(mixed $value): string
    {
        $clean = preg_replace('/\D/', '', (string) $value);
        if (empty($clean)) return '';
        // Already has country code
        if (strlen($clean) >= 11 && str_starts_with($clean, '263')) {
            return '+' . $clean;
        }
        // Local format: starts with 0
        if (str_starts_with($clean, '0')) {
            return '+263' . substr($clean, 1);
        }
        // 9-digit local number
        return '+263' . $clean;
    }
}