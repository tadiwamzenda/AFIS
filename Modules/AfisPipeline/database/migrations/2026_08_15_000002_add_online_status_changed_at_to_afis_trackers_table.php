<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('afis_trackers', function (Blueprint $table) {
            $table->timestamp('online_status_changed_at')->nullable()->after('online_status');
        });
    }

    public function down(): void
    {
        Schema::table('afis_trackers', function (Blueprint $table) {
            $table->dropColumn('online_status_changed_at');
        });
    }
};