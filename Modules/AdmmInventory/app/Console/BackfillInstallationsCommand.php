<?php

namespace Modules\AdmmInventory\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\AdmmInventory\Models\AssetRecord;
use Modules\AdmmInventory\Models\Client;
use Modules\AdmmInventory\Models\GpsDevice;
use Modules\AdmmInventory\Models\InstallationEvent;
use Modules\AdmmInventory\Models\SimCard;
use Modules\AdmmInventory\Models\Technician;
use Modules\AdmmInventory\Services\InstallationService;
use Modules\AdmmInventory\Services\InventoryCsvImporter;

/**
 * One-off: turn Asset Register rows (location = CLIENT) into installed devices,
 * SIM cards and history events, so the Installation Lookup, the duplicate
 * registration check and Repair/Swap work for existing installs.
 *
 *   php artisan admm:backfill-installs            dry run (nothing is saved)
 *   php artisan admm:backfill-installs --commit   save
 *
 * Rows that cannot be matched cleanly are never guessed: they go to the
 * report CSV for manual review. The Asset Register itself is never changed.
 */
class BackfillInstallationsCommand extends Command
{
    protected $signature   = 'admm:backfill-installs {--commit : Save the changes (default is a dry run)}';
    protected $description = 'Backfill installed devices, SIMs and history from the Asset Register';

    private array $report = [];

    public function handle(InventoryCsvImporter $csv): int
    {
        $commit = (bool) $this->option('commit');
        $this->info($commit ? 'COMMIT MODE: changes will be saved.' : 'DRY RUN: nothing will be saved.');

        $rows = AssetRecord::where('location', 'CLIENT')
            ->orderByDesc('installation_date')->orderByDesc('id')->get();

        $clients = Client::all();
        $byName  = [];
        foreach ($clients as $c) {
            $byName[mb_strtolower(trim($c->name))] = $c;
            if ($c->navixy_group_prefix) {
                $byName[mb_strtolower(trim($c->navixy_group_prefix))] ??= $c;
            }
        }

        $seenImei = [];
        $seenReg  = [];
        $counts   = ['imported' => 0, 'skipped' => 0, 'exception' => 0];

        DB::beginTransaction();
        try {
            foreach ($rows as $r) {
                try {
                    $outcome = DB::transaction(fn () => $this->processRow($r, $byName, $seenImei, $seenReg, $csv));
                    $counts[$outcome[0]]++;
                    $this->add($r, $outcome[0], $outcome[1]);
                } catch (\DomainException $e) {
                    $counts['exception']++;
                    $this->add($r, 'exception', $e->getMessage());
                }
            }

            $commit ? DB::commit() : DB::rollBack();
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Aborted, nothing saved: ' . $e->getMessage());
            return self::FAILURE;
        }

        $path = storage_path('app/backfill-installs-' . now()->format('Ymd-His') . ($commit ? '' : '-dryrun') . '.csv');
        $fh = fopen($path, 'w');
        fputcsv($fh, ['asset_row_id', 'imei', 'registration', 'client', 'outcome', 'reason']);
        foreach ($this->report as $line) fputcsv($fh, $line);
        fclose($fh);

        $this->table(['Outcome', 'Rows'], [
            ['Would import / imported', $counts['imported']],
            ['Skipped (already done or superseded)', $counts['skipped']],
            ['Exceptions (need manual review)', $counts['exception']],
        ]);
        $this->line("Report: {$path}");
        if (!$commit) {
            $this->warn('Review the report, then run again with --commit.');
        }

        return self::SUCCESS;
    }

    /** @return array{0:string,1:string} [outcome, reason] */
    private function processRow(AssetRecord $r, array $byName, array &$seenImei, array &$seenReg, InventoryCsvImporter $csv): array
    {
        $imei = preg_replace('/\D+/', '', (string) $r->gps_device_imei);
        if (strlen($imei) !== 15) {
            throw new \DomainException('IMEI missing or not 15 digits');
        }
        if (isset($seenImei[$imei])) {
            return ['skipped', "Duplicate IMEI, already handled from row #{$seenImei[$imei]}"];
        }
        $seenImei[$imei] = $r->id;

        $reg = InstallationService::normalizeReg((string) $r->vehicle_reg_no);
        $key = InstallationService::regKey($reg);
        if ($key === '') {
            throw new \DomainException('No vehicle registration');
        }

        if (isset($seenReg[$key])) {
            return ['skipped', "Superseded: newer record row #{$seenReg[$key]} is on the same registration"];
        }

        $client = $byName[mb_strtolower(trim((string) $r->client))] ?? null;
        if (!$client) {
            throw new \DomainException('Client "' . $r->client . '" not found in Clients');
        }

        $device = GpsDevice::where('imei', $imei)->first();
        if ($device) {
            if ($device->status === 'installed') {
                $seenReg[$key] = $r->id;
                return ['skipped', 'Device already installed'];
            }
            throw new \DomainException("Device already exists with status {$device->status}; review manually");
        }

        $other = GpsDevice::where('status', 'installed')
            ->whereRaw("UPPER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(vehicle_registration,' ',''),'-',''),'.',''),'/',''),'_','')) = ?", [$key])
            ->first();
        if ($other) {
            throw new \DomainException("Registration already has installed device {$other->imei}");
        }

        // SIM
        $sim = null;
        $iccid = preg_replace('/\D+/', '', (string) $r->sim_card_serial_no);
        $warn = [];
        if ($iccid === '') {
            $warn[] = 'no SIM recorded';
        } else {
            if (strlen($iccid) < 18 || strlen($iccid) > 22) {
                throw new \DomainException("SIM ICCID {$iccid} is not 18-22 digits");
            }
            $sim = SimCard::where('iccid', $iccid)->first();
            if ($sim) {
                if (GpsDevice::where('sim_card_id', $sim->id)->exists()) {
                    throw new \DomainException("SIM {$iccid} is already paired to another device");
                }
            } else {
                $raw    = (string) $r->sim_card_phone_no;
                $msisdn = $raw !== '' ? ($csv->normalizeMsisdn($raw) ?? $raw) : null;
                $prov   = $r->sim_card_isp ? ucfirst(strtolower($r->sim_card_isp))
                        : (($msisdn && str_starts_with($msisdn, '+263')) ? $csv->providerFor($msisdn) : 'Unknown');
                $sim = SimCard::create([
                    'iccid'            => $iccid,
                    'msisdn'           => $msisdn ? mb_substr($msisdn, 0, 20) : null,
                    'network_provider' => $prov,
                    'status'           => 'unassigned',
                    'location_context' => 'unallocated',
                ]);
                $warn[] = 'SIM created';
            }
        }

        // Technician
        $tech = null;
        $tname = trim((string) $r->technician);
        if ($tname !== '') {
            $tech = Technician::whereRaw('LOWER(name) = ?', [mb_strtolower($tname)])->first()
                 ?? Technician::create(['name' => mb_substr($tname, 0, 100), 'is_active' => true]);
        }

        $type = trim((string) $r->gps_device_type) ?: 'Other';
        $device = GpsDevice::create([
            'imei'                 => $imei,
            'device_type'          => mb_substr($type, 0, 50),
            'model'                => mb_substr($type, 0, 100),
            'status'               => 'installed',
            'client_id'            => $client->id,
            'sim_card_id'          => $sim?->id,
            'vehicle_registration' => $reg,
            'fleet_number'         => $r->vehicle_fleet_no ?: null,
            'vehicle_make'         => $r->vehicle_make ?: null,
            'installed_at'         => $r->installation_date,
            'technician_id'        => $tech?->id,
            'technician'           => $tech?->name,
        ]);

        if ($sim) {
            $sim->update(['status' => 'active_client', 'location_context' => 'client_assigned', 'client_id' => $client->id]);
        }

        InstallationEvent::create([
            'gps_device_id'        => $device->id,
            'sim_card_id'          => $sim?->id,
            'event_type'           => 'installed',
            'client_id'            => $client->id,
            'vehicle_registration' => $reg,
            'fleet_number'         => $device->fleet_number,
            'vehicle_make'         => $device->vehicle_make,
            'technician_id'        => $tech?->id,
            'notes'                => 'Backfilled from Asset Register row #' . $r->id
                . ($r->installation_date ? ' (installed ' . $r->installation_date->format('d M Y') . ')' : ''),
        ]);

        $seenReg[$key] = $r->id;

        return ['imported', implode('; ', $warn)];
    }

    private function add(AssetRecord $r, string $outcome, string $reason): void
    {
        $this->report[] = [$r->id, $r->gps_device_imei, $r->vehicle_reg_no, $r->client, $outcome, $reason];
    }
}
