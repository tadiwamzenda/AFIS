<?php

namespace Modules\AfisPortal\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\AdmmInventory\Models\Client;
use App\Models\User;

class AfisGeneratedReport extends Model
{
    protected $table    = 'afis_generated_reports';
    protected $fillable = [
        'client_id', 'generated_by', 'report_type',
        'from_date', 'to_date', 'navixy_group_id',
        'filename', 'file_path', 'file_size',
    ];

    protected $casts = [
        'from_date' => 'date',
        'to_date'   => 'date',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}