<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('adm_accessories', function (Blueprint $table) {
            $table->id();
            $table->string('serial_number')->nullable();
            $table->foreignId('accessory_type_id')
                ->constrained('adm_accessory_types')
                ->restrictOnDelete();
            $table->enum('status', [
                'installed_client',
                'in_office_stock',
                'faulty',
                'decommissioned',
                'lost',
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
            $table->foreignId('gps_device_id')
                ->nullable()
                ->constrained('adm_gps_devices')
                ->nullOnDelete();
            $table->date('purchase_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('location_context');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('adm_accessories');
    }
};