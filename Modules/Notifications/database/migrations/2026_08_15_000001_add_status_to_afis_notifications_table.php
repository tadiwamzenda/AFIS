<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('afis_notifications', function (Blueprint $table) {
            // Nullable and unused by every existing notification type —
            // only the offline-critical-with-Functional-comment case sets this.
            $table->string('status')->nullable()->after('severity');
            $table->timestamp('status_changed_at')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('afis_notifications', function (Blueprint $table) {
            $table->dropColumn(['status', 'status_changed_at']);
        });
    }
};