<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('afis_incidents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->foreignId('tracker_id')->nullable()->constrained('afis_trackers')->nullOnDelete();
            $table->unsignedBigInteger('navixy_tracker_id')->nullable();
            $table->string('vehicle_label')->nullable();
            $table->timestamp('incident_date');
            $table->text('description');
            $table->enum('severity', ['minor', 'moderate', 'serious', 'critical'])->nullable();
            $table->enum('status', ['logged', 'analysing', 'completed', 'failed'])->default('logged');
            $table->foreignId('ai_report_id')->nullable()->constrained('afis_ai_reports')->nullOnDelete();
            $table->foreignId('logged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['client_id', 'incident_date']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('afis_incidents');
    }
};