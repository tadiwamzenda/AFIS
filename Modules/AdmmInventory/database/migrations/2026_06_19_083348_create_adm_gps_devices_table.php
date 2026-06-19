<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('adm_gps_devices', function (Blueprint $table) {
            $table->id();
            $table->string('serial_number')->unique();
            $table->string('model', 100);
            $table->string('firmware_version', 50)->nullable();
            $table->date('purchase_date')->nullable();
            $table->date('warranty_expiry_date')->nullable();
            $table->enum('status', [
                'installed_client',
                'in_office_stock',
                'under_repair',
                'awaiting_disposal',
                'decommissioned',
                'lost_stolen',
            ])->default('in_office_stock');
            $table->enum('location_context', [
                'client_assigned',
                'internal_stock',
                'unallocated',
            ])->default('internal_stock');
            $table->foreignId('client_id')
                ->nullable()
                ->constrained('clients')
                ->nullOnDelete();
            $table->foreignId('sim_card_id')
                ->nullable()
                ->constrained('adm_sim_cards')
                ->nullOnDelete();
            $table->unsignedBigInteger('navixy_tracker_id')->nullable()->unique();
            $table->string('vehicle_registration', 20)->nullable();
            $table->timestamp('installed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('location_context');
            $table->index('warranty_expiry_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('adm_gps_devices');
    }
};