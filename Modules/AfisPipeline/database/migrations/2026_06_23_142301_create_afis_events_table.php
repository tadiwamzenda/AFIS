<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('afis_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tracker_id')->constrained('afis_trackers')->cascadeOnDelete();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->unsignedBigInteger('navixy_tracker_id');
            $table->string('event_type', 100);
            $table->timestamp('occurred_at');
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->json('extra_data')->nullable();
            $table->timestamp('synced_at')->useCurrent();

            $table->index(['tracker_id', 'occurred_at']);
            $table->index(['client_id', 'event_type']);
            $table->index('occurred_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('afis_events');
    }
};