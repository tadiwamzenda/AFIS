<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('afis_incidents', function (Blueprint $table) {
            $table->string('report_path', 500)->nullable()->after('ai_report_id');
            $table->string('prepared_by_name')->nullable()->after('report_path');
            $table->string('prepared_by_title')->nullable()->after('prepared_by_name');
            $table->string('reviewed_by_name')->nullable()->after('prepared_by_title');
            $table->string('reviewed_by_title')->nullable()->after('reviewed_by_name');
        });
    }

    public function down(): void
    {
        Schema::table('afis_incidents', function (Blueprint $table) {
            $table->dropColumn(['report_path', 'prepared_by_name', 'prepared_by_title', 'reviewed_by_name', 'reviewed_by_title']);
        });
    }
};