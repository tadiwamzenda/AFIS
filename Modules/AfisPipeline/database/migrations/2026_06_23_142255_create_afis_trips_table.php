<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('afis_trips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tracker_id')->constrained('afis_trackers')->cascadeOnDelete();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->unsignedBigInteger('navixy_tracker_id');
            $table->timestamp('start_time');
            $table->timestamp('end_time')->nullable();
            $table->decimal('distance_km', 8, 2)->default(0);
            $table->decimal('avg_speed_kmh', 6, 2)->default(0);
            $table->decimal('max_speed_kmh', 6, 2)->default(0);
            $table->unsignedInteger('duration_minutes')->default(0);
            $table->unsignedInteger('stops_count')->default(0);
            $table->timestamp('synced_at')->useCurrent();

            $table->index(['tracker_id', 'start_time']);
            $table->index(['client_id', 'start_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('afis_trips');
    }
};