<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('adm_gps_devices', function (Blueprint $table) {
            // Remove old fields
            $table->dropColumn([
                'serial_number',
                'firmware_version',
                'warranty_expiry_date',
                'purchase_date',
            ]);

            // Add new fields
            $table->string('imei', 20)->nullable()->unique()->after('id');
            $table->string('device_type', 50)->nullable()->after('model');
            $table->string('vehicle_make', 100)->nullable()->after('vehicle_registration');
            $table->string('fleet_number', 50)->nullable()->after('vehicle_make');
            $table->string('technician', 100)->nullable()->after('fleet_number');
        });
    }

    public function down(): void
    {
        Schema::table('adm_gps_devices', function (Blueprint $table) {
            $table->dropColumn(['imei', 'device_type', 'vehicle_make', 'fleet_number', 'technician']);
            $table->string('serial_number')->nullable();
            $table->string('firmware_version')->nullable();
            $table->date('warranty_expiry_date')->nullable();
            $table->date('purchase_date')->nullable();
        });
    }
};