<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('afis_offline_report_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->foreignId('tracker_id')->nullable()->constrained('afis_trackers')->nullOnDelete();
            $table->string('title');
            $table->string('file_path', 500);
            $table->string('state_filter')->default('all');
            $table->string('duration_filter')->nullable();
            $table->string('comment_filter')->nullable();
            $table->string('search_term')->nullable();
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('afis_offline_report_files');
    }
};