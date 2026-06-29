<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    const ROLE_BT_ADMIN      = 'bt_admin';
    const ROLE_BT_TECHNICIAN = 'bt_technician';
    const ROLE_BT_SUPPORT    = 'bt_support';
    const ROLE_CLIENT        = 'client';

    const BT_ROLES     = [self::ROLE_BT_ADMIN, self::ROLE_BT_TECHNICIAN, self::ROLE_BT_SUPPORT];
    const CLIENT_ROLES = [self::ROLE_CLIENT];

    protected $fillable = [
        'name', 'email', 'navixy_user_id', 'navixy_account_id',
        'navixy_instance', 'navixy_security_group_id',
        'role', 'is_active',
    ];

    protected $hidden = ['remember_token'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function isBtStaff(): bool
    {
        return in_array($this->role, self::BT_ROLES);
    }

    public function isClientUser(): bool
    {
        return $this->role === self::ROLE_CLIENT;
    }

    public function isBtAdmin(): bool
    {
        return $this->role === self::ROLE_BT_ADMIN;
    }
}