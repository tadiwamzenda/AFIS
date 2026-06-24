<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('afis_trackers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->unsignedBigInteger('navixy_tracker_id')->unique();
            $table->string('label');
            $table->string('model_name')->nullable();
            $table->string('vehicle_registration')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_active_at')->nullable();
            $table->decimal('last_lat', 10, 7)->nullable();
            $table->decimal('last_lng', 10, 7)->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->index('client_id');
            $table->index('navixy_tracker_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('afis_trackers');
    }
};