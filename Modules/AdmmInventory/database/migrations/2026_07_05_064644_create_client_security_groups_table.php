<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_security_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->unsignedBigInteger('navixy_security_group_id');
            $table->unsignedTinyInteger('navixy_instance')->default(1);
            $table->string('label', 100)->nullable();
            $table->timestamps();

            $table->unique(['navixy_security_group_id', 'navixy_instance'], 'csg_group_instance_unique');
            $table->index('client_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_security_groups');
    }
};