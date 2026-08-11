<?php

namespace Modules\AdmmInventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    protected $fillable = [
    'name',
    'navixy_account_id',
    'navixy_instance',
    'navixy_instance_secondary',  // ADD THIS
    'navixy_security_group_id',
    'navixy_group_prefix',
    'contact_person',
    'contact_email',
    'contact_phone',
    'is_active',
    'notes',
];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function simCards(): HasMany
    {
        return $this->hasMany(SimCard::class);
    }

    public function gpsDevices(): HasMany
    {
        return $this->hasMany(GpsDevice::class);
    }

    public function accessories(): HasMany
    {
        return $this->hasMany(Accessory::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}