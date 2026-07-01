<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('afis_trackers', function (Blueprint $table) {
            $table->string('imei', 20)->nullable()->after('navixy_tracker_id');
            $table->index('imei');
        });
    }

    public function down(): void
    {
        Schema::table('afis_trackers', function (Blueprint $table) {
            $table->dropColumn('imei');
        });
    }
};