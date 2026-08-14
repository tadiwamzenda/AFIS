<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('afis_ai_reports', function (Blueprint $table) {
            $table->string('report_path', 500)->nullable()->after('response');
        });
    }

    public function down(): void
    {
        Schema::table('afis_ai_reports', function (Blueprint $table) {
            $table->dropColumn('report_path');
        });
    }
};