<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    Public function up(): void
    {
        Schema::table('afis_trips', function (Blueprint $table) {
            $table->unsignedInteger('navixy_trip_id')->nullable()->after('navixy_tracker_id');
            $table->unique(['tracker_id', 'navixy_trip_id'], 'afis_trips_tracker_navixy_trip_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('afis_trips', function (Blueprint $table) {
            $table->dropUnique('afis_trips_tracker_navixy_trip_unique');
            $table->dropColumn('navixy_trip_id');
        });
    }
};
