<?php

namespace Modules\AdmmInventory\Console;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Modules\AdmmInventory\Services\StockSnapshotService;

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
        $this->info('Snapshot complete.');
    }
}