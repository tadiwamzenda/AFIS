<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Disable strict mode temporarily to allow enum value changes
        DB::statement("SET SESSION sql_mode = ''");

        // First expand the enum to include all old AND new values
        DB::statement("ALTER TABLE adm_gps_devices MODIFY COLUMN status VARCHAR(50) NOT NULL DEFAULT 'in_office_stock'");

        // Now normalize all existing values
        DB::statement("UPDATE adm_gps_devices SET status = 'installed' WHERE status IN ('installed_client', 'client_assigned')");
        DB::statement("UPDATE adm_gps_devices SET status = 'in_office_stock' WHERE status NOT IN ('in_office_stock','installed','decommissioned','lost_stolen')");

        // Now apply the clean enum
        DB::statement("ALTER TABLE adm_gps_devices MODIFY COLUMN status ENUM('in_office_stock','installed','decommissioned','lost_stolen') NOT NULL DEFAULT 'in_office_stock'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE adm_gps_devices MODIFY COLUMN status VARCHAR(50) NOT NULL DEFAULT 'in_office_stock'");
    }
};