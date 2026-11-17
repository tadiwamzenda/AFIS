<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('adm_technicians', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->string('phone', 30)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('adm_installation_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gps_device_id')->nullable()->constrained('adm_gps_devices')->nullOnDelete();
            $table->foreignId('sim_card_id')->nullable()->constrained('adm_sim_cards')->nullOnDelete();
            // paired, assigned, installed, repair_swap, uncoupled, installer_changed, edited
            $table->string('event_type', 30);
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->string('vehicle_registration', 30)->nullable();
            $table->string('fleet_number', 50)->nullable();
            $table->string('vehicle_make', 150)->nullable();
            $table->string('installation_location', 150)->nullable();
            $table->foreignId('technician_id')->nullable()->constrained('adm_technicians')->nullOnDelete();
            $table->unsignedBigInteger('performed_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['gps_device_id', 'created_at']);
            $table->index('vehicle_registration');
            $table->index('event_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('adm_installation_events');
        Schema::dropIfExists('adm_technicians');
    }
};
