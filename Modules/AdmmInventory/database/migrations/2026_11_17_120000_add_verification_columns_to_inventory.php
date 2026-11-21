<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['adm_gps_devices', 'adm_sim_cards'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->timestamp('last_verified_at')->nullable();
                $table->unsignedBigInteger('last_verified_by')->nullable();
                $table->index('last_verified_at');
            });
        }
    }

    public function down(): void
    {
        foreach (['adm_gps_devices', 'adm_sim_cards'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                $table->dropIndex("{$tableName}_last_verified_at_index");
                $table->dropColumn(['last_verified_at', 'last_verified_by']);
            });
        }
    }
};
