<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('afis_ai_report_cache', function (Blueprint $table) {
            $table->id();
            $table->string('cache_key', 255)->unique();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->unsignedBigInteger('tracker_id')->nullable();
            $table->string('report_type', 100);
            $table->longText('response');
            $table->string('engine_used', 50);
            $table->timestamp('generated_at')->useCurrent();
            $table->timestamp('expires_at');

            $table->index('cache_key');
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('afis_ai_report_cache');
    }
};