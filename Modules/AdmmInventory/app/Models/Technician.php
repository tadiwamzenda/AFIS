<?php

namespace Modules\AdmmInventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Technician extends Model
{
    protected $table    = 'adm_technicians';
    protected $fillable = ['name', 'phone', 'is_active'];
    protected $casts    = ['is_active' => 'boolean'];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function devices(): HasMany
    {
        return $this->hasMany(GpsDevice::class, 'technician_id');
    }
}
