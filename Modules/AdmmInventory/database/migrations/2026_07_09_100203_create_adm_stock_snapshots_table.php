<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('adm_stock_snapshots', function (Blueprint $table) {
            $table->id();
            $table->date('snapshot_date');
            $table->string('parent_client', 200)->default('UNASSIGNED');
            $table->unsignedInteger('devices_total')->default(0);
            $table->unsignedInteger('devices_with_client')->default(0);
            $table->unsignedInteger('devices_in_stock')->default(0);
            $table->unsignedInteger('devices_lost')->default(0);
            $table->unsignedInteger('sims_total')->default(0);
            $table->unsignedInteger('sims_with_client')->default(0);
            $table->unsignedInteger('sims_in_stock')->default(0);
            $table->unsignedInteger('sims_lost')->default(0);
            $table->timestamps();

            $table->unique(['snapshot_date', 'parent_client']);
            $table->index('snapshot_date');
            $table->index('parent_client');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('adm_stock_snapshots');
    }
};