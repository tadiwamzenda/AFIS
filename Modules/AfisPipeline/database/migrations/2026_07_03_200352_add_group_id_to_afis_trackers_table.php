<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('afis_trackers', function (Blueprint $table) {
            $table->unsignedBigInteger('navixy_group_id')->nullable()->after('imei');
            $table->string('vehicle_label', 200)->nullable()->after('label');
            $table->enum('online_status', ['online', 'offline', 'unknown'])->default('unknown')->after('is_active');
            $table->index('navixy_group_id');
        });
    }

    public function down(): void
    {
        Schema::table('afis_trackers', function (Blueprint $table) {
            $table->dropColumn(['navixy_group_id', 'vehicle_label', 'online_status']);
        });
    }
};