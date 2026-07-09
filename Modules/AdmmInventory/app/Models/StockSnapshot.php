<?php

namespace Modules\AdmmInventory\Models;

use Illuminate\Database\Eloquent\Model;

class StockSnapshot extends Model
{
    protected $table    = 'adm_stock_snapshots';
    protected $fillable = [
        'snapshot_date', 'parent_client',
        'devices_total', 'devices_with_client', 'devices_in_stock', 'devices_lost',
        'sims_total', 'sims_with_client', 'sims_in_stock', 'sims_lost',
    ];

    protected $casts = ['snapshot_date' => 'date'];
}