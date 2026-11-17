<?php

namespace Modules\AdmmInventory\Services;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Modules\AdmmInventory\Models\GpsDevice;
use Modules\AdmmInventory\Models\SimCard;

/**
 * CSV batch import for devices (IMEI) and SIM cards (ICCID).
 *
 * parseDevices()/parseSims() are pure (no database): they validate every row
 * and return the valid rows plus a list of failures. importDevices()/importSims()
 * load existing identifiers, call the parser, then save the valid rows.
 */
class InventoryCsvImporter
{
    public const MAX_ROWS = 5000;

    // ─── Devices ─────────────────────────────────────────────────────────────

    public function importDevices(string $path): array
    {
        $existing = [];
        foreach (GpsDevice::whereNotNull('imei')->pluck('imei') as $imei) {
            $existing[preg_replace('/\s+/', '', (string) $imei)] = true;
        }

        $parsed = $this->parseDevices($path, $existing);
        if ($parsed['fatal']) {
            return $this->fatal($parsed['fatal']);
        }

        $failed   = $parsed['failed'];
        $imported = 0;

        DB::transaction(function () use ($parsed, &$failed, &$imported) {
            foreach ($parsed['valid'] as [$row, $data]) {
                try {
                    GpsDevice::create($data);
                    $imported++;
                } catch (QueryException $e) {
                    $failed[] = $this->fail($row, 'imei', $data['imei'], 'Could not be saved (duplicate or invalid data)');
                }
            }
        });

        return $this->result($parsed['total'], $imported, $failed);
    }

    public function parseDevices(string $path, array $existingImeis = []): array
    {
        $csv = $this->readCsv($path, [
            'imei'        => ['imei'],
            'device_type' => ['device_type', 'type', 'devicetype'],
            'model'       => ['model'],
        ], ['imei', 'device_type']);

        if ($csv['error']) {
            return ['fatal' => $csv['error'], 'valid' => [], 'failed' => [], 'total' => 0];
        }

        $valid  = [];
        $failed = [];
        $seen   = [];

        foreach ($csv['rows'] as [$n, $r]) {
            $imeiRaw = trim((string) ($r['imei'] ?? ''));
            $type    = trim((string) ($r['device_type'] ?? ''));
            $model   = trim((string) ($r['model'] ?? ''));
            $ok      = true;
            $imei    = preg_replace('/\s+/', '', $imeiRaw);

            if ($imeiRaw === '') {
                $failed[] = $this->fail($n, 'imei', $imeiRaw, 'IMEI is required');
                $ok = false;
            } elseif ($this->looksScientific($imeiRaw)) {
                $failed[] = $this->fail($n, 'imei', $imeiRaw, 'IMEI was converted to scientific notation by Excel. Format the column as Text and re-save the CSV');
                $ok = false;
            } elseif (!preg_match('/^\d{15}$/', $imei)) {
                $failed[] = $this->fail($n, 'imei', $imeiRaw, 'IMEI must be exactly 15 digits');
                $ok = false;
            } elseif (isset($seen[$imei])) {
                $failed[] = $this->fail($n, 'imei', $imeiRaw, "Duplicate IMEI in this file (first used on row {$seen[$imei]})");
                $ok = false;
            } elseif (isset($existingImeis[$imei])) {
                $failed[] = $this->fail($n, 'imei', $imeiRaw, 'IMEI already exists in the system');
                $ok = false;
            }

            if ($type === '') {
                $failed[] = $this->fail($n, 'device_type', $type, 'Device_Type is required');
                $ok = false;
            } elseif (mb_strlen($type) > 50) {
                $failed[] = $this->fail($n, 'device_type', $type, 'Device_Type must be 50 characters or fewer');
                $ok = false;
            }

            if ($ok) {
                $seen[$imei] = $n;
                $valid[] = [$n, [
                    'imei'        => $imei,
                    'device_type' => $type,
                    // adm_gps_devices.model may be NOT NULL: fall back to the type
                    'model'       => $model !== '' ? mb_substr($model, 0, 100) : $type,
                    'status'      => 'in_office_stock',
                ]];
            }
        }

        return ['fatal' => null, 'valid' => $valid, 'failed' => $this->sortFailed($failed), 'total' => count($csv['rows'])];
    }

    // ─── SIM cards ───────────────────────────────────────────────────────────

    public function importSims(string $path): array
    {
        $existingIccids = [];
        $existingPhones = [];
        foreach (SimCard::get(['iccid', 'msisdn']) as $sim) {
            $existingIccids[preg_replace('/[\s-]+/', '', (string) $sim->iccid)] = true;
            if ($sim->msisdn) {
                $key = $this->normalizeMsisdn($sim->msisdn) ?? preg_replace('/\D+/', '', $sim->msisdn);
                $existingPhones[$key] = true;
            }
        }

        $parsed = $this->parseSims($path, $existingIccids, $existingPhones);
        if ($parsed['fatal']) {
            return $this->fatal($parsed['fatal']);
        }

        $failed   = $parsed['failed'];
        $imported = 0;

        DB::transaction(function () use ($parsed, &$failed, &$imported) {
            foreach ($parsed['valid'] as [$row, $data]) {
                try {
                    SimCard::create($data);
                    $imported++;
                } catch (QueryException $e) {
                    $failed[] = $this->fail($row, 'iccid', $data['iccid'], 'Could not be saved (duplicate or invalid data)');
                }
            }
        });

        return $this->result($parsed['total'], $imported, $failed);
    }

    public function parseSims(string $path, array $existingIccids = [], array $existingPhones = []): array
    {
        $csv = $this->readCsv($path, [
            'iccid'        => ['iccid'],
            'phone_number' => ['phone_number', 'msisdn', 'phone', 'phonenumber', 'mobile'],
        ], ['iccid', 'phone_number']);

        if ($csv['error']) {
            return ['fatal' => $csv['error'], 'valid' => [], 'failed' => [], 'total' => 0];
        }

        $valid      = [];
        $failed     = [];
        $seenIccid  = [];
        $seenPhone  = [];

        foreach ($csv['rows'] as [$n, $r]) {
            $iccidRaw = trim((string) ($r['iccid'] ?? ''));
            $phoneRaw = trim((string) ($r['phone_number'] ?? ''));
            $iccid    = preg_replace('/[\s-]+/', '', $iccidRaw);
            $msisdn   = null;
            $ok       = true;

            if ($iccidRaw === '') {
                $failed[] = $this->fail($n, 'iccid', $iccidRaw, 'ICCID is required');
                $ok = false;
            } elseif ($this->looksScientific($iccidRaw)) {
                $failed[] = $this->fail($n, 'iccid', $iccidRaw, 'ICCID was converted to scientific notation by Excel. Format the column as Text and re-save the CSV');
                $ok = false;
            } elseif (!preg_match('/^\d{18,22}$/', $iccid)) {
                $failed[] = $this->fail($n, 'iccid', $iccidRaw, 'ICCID must be 18 to 22 digits');
                $ok = false;
            } elseif (isset($seenIccid[$iccid])) {
                $failed[] = $this->fail($n, 'iccid', $iccidRaw, "Duplicate ICCID in this file (first used on row {$seenIccid[$iccid]})");
                $ok = false;
            } elseif (isset($existingIccids[$iccid])) {
                $failed[] = $this->fail($n, 'iccid', $iccidRaw, 'ICCID already exists in the system');
                $ok = false;
            }

            if ($phoneRaw === '') {
                $failed[] = $this->fail($n, 'phone_number', $phoneRaw, 'Phone_Number is required');
                $ok = false;
            } elseif ($this->looksScientific($phoneRaw) || ($msisdn = $this->normalizeMsisdn($phoneRaw)) === null) {
                $failed[] = $this->fail($n, 'phone_number', $phoneRaw, 'Not a valid Zimbabwe mobile number (expected e.g. 0771234567 or +263771234567)');
                $ok = false;
            } elseif (isset($seenPhone[$msisdn])) {
                $failed[] = $this->fail($n, 'phone_number', $phoneRaw, "Duplicate phone number in this file (first used on row {$seenPhone[$msisdn]})");
                $ok = false;
            } elseif (isset($existingPhones[$msisdn])) {
                $failed[] = $this->fail($n, 'phone_number', $phoneRaw, 'Phone number already belongs to another SIM');
                $ok = false;
            }

            if ($ok) {
                $seenIccid[$iccid]  = $n;
                $seenPhone[$msisdn] = $n;
                $valid[] = [$n, [
                    'iccid'            => $iccid,
                    'msisdn'           => $msisdn,
                    'network_provider' => $this->providerFor($msisdn),
                    'status'           => 'unassigned',
                    'location_context' => 'internal_stock',
                ]];
            }
        }

        return ['fatal' => null, 'valid' => $valid, 'failed' => $this->sortFailed($failed), 'total' => count($csv['rows'])];
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    /** Returns +263XXXXXXXXX for a valid Zimbabwe mobile number, otherwise null. */
    public function normalizeMsisdn(string $raw): ?string
    {
        $d = preg_replace('/\D+/', '', $raw);

        if (str_starts_with($d, '263')) {
            $d = substr($d, 3);
        } elseif (str_starts_with($d, '0')) {
            $d = substr($d, 1);
        }

        return preg_match('/^7[1378]\d{7}$/', $d) ? '+263' . $d : null;
    }

    public function providerFor(string $msisdn): string
    {
        return match (substr($msisdn, 4, 2)) {
            '77', '78' => 'Econet',
            '71'       => 'NetOne',
            '73'       => 'Telecel',
            default    => 'Unknown',
        };
    }

    private function looksScientific(string $v): bool
    {
        return (bool) preg_match('/^\d+(\.\d+)?e[+-]?\d+$/i', trim($v));
    }

    private function fail(int $row, string $field, string $value, string $reason): array
    {
        return ['row' => $row, 'field' => $field, 'value' => $value, 'reason' => $reason];
    }

    private function sortFailed(array $failed): array
    {
        usort($failed, fn($a, $b) => $a['row'] <=> $b['row']);
        return $failed;
    }

    private function result(int $total, int $imported, array $failed): array
    {
        return [
            'total'    => $total,
            'imported' => $imported,
            'failed'   => $this->sortFailed($failed),
            'fatal'    => null,
        ];
    }

    private function fatal(string $message): array
    {
        return ['total' => 0, 'imported' => 0, 'failed' => [], 'fatal' => $message];
    }

    /**
     * @param array<string, string[]> $aliases  canonical column => accepted header names
     * @param string[]                $required canonical columns that must be present
     * @return array{rows: array<int, array{0:int,1:array<string,string>}>, error: ?string}
     */
    private function readCsv(string $path, array $aliases, array $required): array
    {
        $h = @fopen($path, 'r');
        if (!$h) {
            return ['rows' => [], 'error' => 'The file could not be read.'];
        }

        $first = fgets($h);
        if ($first === false || trim($first) === '') {
            fclose($h);
            return ['rows' => [], 'error' => 'The file is empty.'];
        }

        $first = preg_replace('/^\xEF\xBB\xBF/', '', $first);
        $delim = substr_count($first, ';') > substr_count($first, ',') ? ';' : ',';
        $header = str_getcsv($first, $delim, '"', '');

        $index = [];
        foreach ($header as $i => $label) {
            $key = trim(preg_replace('/[^a-z0-9]+/', '_', strtolower(trim((string) $label))), '_');
            foreach ($aliases as $canon => $names) {
                if (in_array($key, $names, true) && !isset($index[$canon])) {
                    $index[$canon] = $i;
                }
            }
        }

        $missing = array_values(array_diff($required, array_keys($index)));
        if ($missing) {
            fclose($h);
            return ['rows' => [], 'error' => 'Missing required column(s): '
                . implode(', ', array_map('strtoupper', $missing))
                . '. Columns found: ' . implode(', ', array_map('trim', $header))];
        }

        $rows = [];
        $line = 1;
        while (($cols = fgetcsv($h, 0, $delim, '"', '')) !== false) {
            $line++;
            if (count(array_filter($cols, fn($c) => trim((string) $c) !== '')) === 0) {
                continue; // blank line
            }
            if (count($rows) >= self::MAX_ROWS) {
                fclose($h);
                return ['rows' => [], 'error' => 'Too many rows (maximum ' . self::MAX_ROWS . ' per file). Split the file and import it in parts.'];
            }
            $row = [];
            foreach ($index as $canon => $i) {
                $row[$canon] = $cols[$i] ?? '';
            }
            $rows[] = [$line, $row];
        }
        fclose($h);

        if (!$rows) {
            return ['rows' => [], 'error' => 'The file has a header row but no data rows.'];
        }

        return ['rows' => $rows, 'error' => null];
    }
}
