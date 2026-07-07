<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('afis_fuel_readings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tracker_id')->constrained('afis_trackers')->cascadeOnDelete();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->unsignedBigInteger('navixy_tracker_id');
            $table->unsignedBigInteger('sensor_id');
            $table->decimal('value_litres', 8, 2);
            $table->timestamp('reading_time');
            $table->timestamp('synced_at')->useCurrent();

            $table->index(['tracker_id', 'reading_time']);
            $table->index(['client_id', 'reading_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('afis_fuel_readings');
    }
};