<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Action party + "when was Comment/Action Party last changed"
        Schema::table('afis_offline_incidents', function (Blueprint $table) {
            $table->string('action_party', 20)->nullable()->default('Bantu Track');
            $table->timestamp('triage_updated_at')->nullable();
            $table->unsignedBigInteger('triage_updated_by')->nullable();
        });

        // 2. Comment becomes a plain string with a default, so new incidents start as Functional (Active)
        Schema::table('afis_offline_incidents', function (Blueprint $table) {
            $table->string('comment', 50)->nullable()->default('Functional (Active)')->change();
        });

        // 3. Map the old comment values onto the new ones
        foreach ([
            'Accident'   => 'Accident (Damaged)',
            'Garage'     => 'Garage (Repairs)',
            'Functional' => 'Functional (Active)',
        ] as $old => $new) {
            DB::table('afis_offline_incidents')->where('comment', $old)->update(['comment' => $new]);
        }

        // 4. Only vehicles that are offline right now get the defaults; history stays as recorded
        DB::table('afis_offline_incidents')->whereNull('came_online_at')->whereNull('comment')
            ->update(['comment' => 'Functional (Active)']);
        DB::table('afis_offline_incidents')->whereNotNull('came_online_at')
            ->update(['action_party' => null]);

        // 5. Saved reports remember the Action Party filter too
        Schema::table('afis_offline_report_files', function (Blueprint $table) {
            $table->string('action_party_filter', 20)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('afis_offline_report_files', fn (Blueprint $t) => $t->dropColumn('action_party_filter'));
        Schema::table('afis_offline_incidents', fn (Blueprint $t) => $t->dropColumn(['action_party', 'triage_updated_at', 'triage_updated_by']));
    }
};
