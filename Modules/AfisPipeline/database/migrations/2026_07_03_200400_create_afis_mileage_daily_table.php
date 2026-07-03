<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('afis_mileage_daily', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tracker_id')->constrained('afis_trackers')->cascadeOnDelete();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->unsignedBigInteger('navixy_tracker_id');
            $table->date('date');
            $table->decimal('mileage_km', 8, 2)->default(0);
            $table->timestamp('synced_at')->useCurrent();

            $table->unique(['tracker_id', 'date']);
            $table->index(['client_id', 'date']);
            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('afis_mileage_daily');
    }
};