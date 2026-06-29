<?php

namespace Modules\Notifications\Console;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Modules\AdmmInventory\Models\GpsDevice;
use Modules\AdmmInventory\Models\SimCard;
use Modules\Notifications\Services\NotificationService;

class CheckAlertsCommand extends Command
{
    protected $signature   = 'afis:check-alerts';
    protected $description = 'Check for SIM renewals, device warranties, and other alerts';

    public function handle(NotificationService $notifications): void
    {
        $this->info('Checking alerts...');

        $checked = 0;
        $fired   = 0;

        // ── SIM bundle renewals ──────────────────────────────────────────────
        $thresholds = [7, 14, 30];

        foreach ($thresholds as $days) {
            $date = Carbon::now()->addDays($days)->toDateString();

            $sims = SimCard::whereDate('bundle_renewal_date', $date)
                ->whereNotIn('status', ['deactivated', 'lost'])
                ->get();

            foreach ($sims as $sim) {
                $notifications->simBundleRenewalDue($sim, $days);
                $fired++;
                $this->line("  → SIM renewal alert: {$sim->msisdn} ({$days} days)");
            }
            $checked += $sims->count();
        }

        // ── Device warranties ────────────────────────────────────────────────
        foreach ($thresholds as $days) {
            $date = Carbon::now()->addDays($days)->toDateString();

            $devices = GpsDevice::whereDate('warranty_expiry_date', $date)
                ->whereNotIn('status', ['decommissioned', 'lost_stolen'])
                ->get();

            foreach ($devices as $device) {
                $notifications->deviceWarrantyExpiring($device, $days);
                $fired++;
                $this->line("  → Warranty alert: {$device->serial_number} ({$days} days)");
            }
            $checked += $devices->count();
        }

        $this->info("Done. Checked {$checked} assets, fired {$fired} alerts.");
    }
}