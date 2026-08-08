<?php

namespace Modules\AdmmInventory\Console;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Modules\AdmmInventory\Services\StockSnapshotService;
use Illuminate\Support\Facades\Cache;
use Modules\AfisPipeline\Models\AfisTracker;

class TakeStockSnapshotCommand extends Command
{
    protected $signature   = 'admm:snapshot {--date= : Date to snapshot (Y-m-d, default today)}';
    protected $description = 'Take a stock snapshot from the Asset Register';

    public function handle(StockSnapshotService $service): void
    {
        $date = $this->option('date')
            ? Carbon::parse($this->option('date'))
            : Carbon::today();

        $this->info("Taking stock snapshot for {$date->format('d M Y')}...");
        $service->takeSnapshot($date);

        // Store daily offline vehicle count snapshot
        $offlineCount = AfisTracker::where('client_id', '!=', 21)
            ->where('online_status', 'offline')
            ->count();
        Cache::put('daily_offline_snapshot_' . $date->toDateString(), $offlineCount, 86400 * 90);
        $this->info("Offline snapshot: {$offlineCount} vehicles offline.");

        $this->info('Snapshot complete.');
    }
}