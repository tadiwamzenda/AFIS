<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('afis_ai_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->unsignedBigInteger('tracker_id')->nullable();
            $table->string('report_type', 100);
            $table->string('engine_used', 50)->default('claude');
            $table->longText('prompt_used');
            $table->longText('response');
            $table->unsignedInteger('tokens_used')->default(0);
            $table->unsignedInteger('duration_ms')->default(0);
            $table->enum('status', ['pending', 'completed', 'failed'])->default('pending');
            $table->text('error_message')->nullable();
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['client_id', 'report_type']);
            $table->index(['tracker_id', 'report_type']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('afis_ai_reports');
    }
};