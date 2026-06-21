<?php

namespace Modules\AdmmDocuments\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class SimImport extends Model
{
    protected $table = 'adm_sim_imports';

    public $timestamps = false;

    protected $fillable = [
        'provider',
        'filename',
        'total_rows',
        'created_count',
        'updated_count',
        'skipped_count',
        'imported_by',
    ];

    public function importedBy()
    {
        return $this->belongsTo(User::class, 'imported_by');
    }
}