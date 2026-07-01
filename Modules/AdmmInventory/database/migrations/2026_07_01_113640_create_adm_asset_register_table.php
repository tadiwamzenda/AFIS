<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('adm_asset_register', function (Blueprint $table) {
            $table->id();
            $table->date('installation_date')->nullable();
            $table->string('client', 150)->nullable();
            $table->string('vehicle_reg_no', 30)->nullable();
            $table->string('vehicle_fleet_no', 50)->nullable();
            $table->string('vehicle_make', 150)->nullable();
            $table->string('gps_device_imei', 20)->nullable();
            $table->string('gps_device_name', 100)->nullable();
            $table->string('gps_device_type', 50)->nullable();
            $table->string('configuration', 100)->nullable();
            $table->enum('gps_device_state', ['ACTIVE', 'INACTIVE', 'MALFUNCTION'])->default('ACTIVE');
            $table->string('sim_card_serial_no', 30)->nullable();
            $table->string('sim_card_phone_no', 20)->nullable();
            $table->enum('sim_card_type', ['MULTIMEDIA', 'TELEMETRY'])->nullable();
            $table->enum('sim_card_isp', ['ECONET', 'NETONE', 'GLOBAL'])->nullable();
            $table->enum('location', ['CLIENT', 'STOCK', 'LOST'])->default('STOCK');
            $table->string('technician', 100)->nullable();
            $table->text('comment')->nullable();
            $table->string('client_name', 150)->nullable();
            $table->string('client_contact', 30)->nullable();
            $table->string('client_email', 150)->nullable();
            $table->timestamps();

            $table->index('client');
            $table->index('location');
            $table->index('gps_device_imei');
            $table->index('gps_device_state');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('adm_asset_register');
    }
};