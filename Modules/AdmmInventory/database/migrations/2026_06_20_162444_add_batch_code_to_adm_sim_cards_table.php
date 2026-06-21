<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    //
    public function up(): void
    {
        Schema::table('adm_sim_cards', function (Blueprint $table) {
            $table->string('batch_code', 100)->nullable()->after('network_provider');
        });
    }

    public function down(): void
    {
        Schema::table('adm_sim_cards', function (Blueprint $table) {
            $table->dropColumn('batch_code');
        });
    }
};