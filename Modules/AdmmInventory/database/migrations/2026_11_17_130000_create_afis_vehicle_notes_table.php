<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('afis_vehicle_notes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tracker_id')->index();   // afis tracker id (no FK: different module)
            $table->unsignedBigInteger('client_id')->index();
            $table->string('tag', 30);
            $table->string('note', 500);
            $table->date('expires_at')->nullable();              // note stops counting after this date
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('resolved_at')->nullable()->index();
            $table->unsignedBigInteger('resolved_by')->nullable();
            $table->timestamps();

            $table->index(['client_id', 'resolved_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('afis_vehicle_notes');
    }
};
