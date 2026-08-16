<?php

namespace Modules\AfisPipeline\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\AdmmInventory\Models\Client;
use App\Models\User;

class AfisOfflineReportFile extends Model
{
    protected $table = 'afis_offline_report_files';

    protected $fillable = [
        'client_id', 'tracker_id', 'title', 'file_path',
        'state_filter', 'duration_filter', 'comment_filter',
        'search_term', 'generated_by',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function tracker(): BelongsTo
    {
        return $this->belongsTo(AfisTracker::class);
    }

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}