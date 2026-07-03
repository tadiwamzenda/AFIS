<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('afis_tracker_groups', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('navixy_group_id')->unique();
            $table->unsignedTinyInteger('navixy_instance')->default(1);
            $table->string('title', 200);
            $table->string('color', 10)->nullable();
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->timestamps();

            $table->index(['navixy_instance', 'navixy_group_id']);
            $table->index('client_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('afis_tracker_groups');
    }
};