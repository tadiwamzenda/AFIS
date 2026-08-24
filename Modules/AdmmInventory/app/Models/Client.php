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
    'navixy_instance_secondary',
    'navixy_api_key',
    'navixy_security_group_id',
    'navixy_group_prefix',
    'contact_person',
    'contact_email',
    'contact_phone',
    'is_active',
    'notes',
];

    protected $casts = [
        'is_active'      => 'boolean',
        // Encrypted at rest — this is a live, reusable Navixy account
        // credential (used for independent-account clients, per today's
        // multi-account migration work). Transparent to every other part
        // of the app that reads $client->navixy_api_key; only the raw DB
        // column changes.
        'navixy_api_key' => 'encrypted',
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