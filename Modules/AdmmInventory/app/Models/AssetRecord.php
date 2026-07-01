<?php

namespace Modules\AdmmInventory\Models;

use Illuminate\Database\Eloquent\Model;

class AssetRecord extends Model
{
    protected $table    = 'adm_asset_register';
    protected $fillable = [
        'installation_date', 'client', 'vehicle_reg_no', 'vehicle_fleet_no',
        'vehicle_make', 'gps_device_imei', 'gps_device_name', 'gps_device_type',
        'configuration', 'gps_device_state', 'sim_card_serial_no', 'sim_card_phone_no',
        'sim_card_type', 'sim_card_isp', 'location', 'technician', 'comment',
        'client_name', 'client_contact', 'client_email',
    ];

    protected $casts = [
        'installation_date' => 'date',
    ];

    const DEVICE_STATES = ['ACTIVE', 'INACTIVE', 'MALFUNCTION'];
    const SIM_TYPES     = ['MULTIMEDIA', 'TELEMETRY'];
    const SIM_ISPS      = ['ECONET', 'NETONE', 'GLOBAL'];
    const LOCATIONS     = ['CLIENT', 'STOCK', 'LOST'];
    const DEVICE_TYPES  = [
        'MT100', 'MT 100', 'VT100', 'VT 100', 'VT100-L',
        'VT200', 'VT 200', 'GT06N', 'FMC920', 'FMB140', 'Other'
    ];
}