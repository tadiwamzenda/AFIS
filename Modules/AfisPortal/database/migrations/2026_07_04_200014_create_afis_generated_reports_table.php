<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('afis_generated_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('report_type', ['standard', 'ai']);
            $table->date('from_date');
            $table->date('to_date');
            $table->unsignedBigInteger('navixy_group_id')->nullable();
            $table->string('filename', 255);
            $table->string('file_path', 500);
            $table->unsignedInteger('file_size')->default(0);
            $table->timestamps();

            $table->index(['client_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('afis_generated_reports');
    }
};