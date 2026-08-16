<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('afis_offline_incidents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tracker_id')->constrained('afis_trackers')->cascadeOnDelete();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->timestamp('went_offline_at');
            $table->timestamp('came_online_at')->nullable(); // null = still offline / incident open
            $table->enum('comment', ['Accident', 'Garage', 'Functional'])->nullable();
            $table->text('resolution')->nullable();
            $table->foreignId('notification_id')->nullable()->constrained('afis_notifications')->nullOnDelete();
            $table->timestamps();

            $table->index(['tracker_id', 'came_online_at']); // fast "is there an open incident for this tracker" lookup
            $table->index(['client_id', 'went_offline_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('afis_offline_incidents');
    }
};