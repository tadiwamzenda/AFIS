<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->unsignedTinyInteger('navixy_instance')->default(1)->after('navixy_account_id');
            $table->unsignedBigInteger('navixy_security_group_id')->nullable()->after('navixy_instance');
            $table->index('navixy_security_group_id');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['navixy_instance', 'navixy_security_group_id']);
        });
    }
};