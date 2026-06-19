<?php

namespace Modules\AdmmInventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccessoryType extends Model
{
    protected $table = 'adm_accessory_types';

    protected $fillable = ['name', 'description', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function accessories(): HasMany
    {
        return $this->hasMany(Accessory::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}