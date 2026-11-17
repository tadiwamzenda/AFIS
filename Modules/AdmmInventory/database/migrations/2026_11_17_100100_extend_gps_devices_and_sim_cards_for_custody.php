<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('adm_gps_devices', function (Blueprint $table) {
            $table->string('installation_location', 150)->nullable()->after('fleet_number');
            $table->foreignId('technician_id')->nullable()->after('technician')
                ->constrained('adm_technicians')->nullOnDelete();
        });

        // Supersets of the current values, so existing rows stay valid.
        DB::statement("ALTER TABLE adm_gps_devices MODIFY COLUMN status ENUM('in_office_stock','with_technician','installed','faulty','decommissioned','lost_stolen') NOT NULL DEFAULT 'in_office_stock'");
        DB::statement("ALTER TABLE adm_sim_cards MODIFY COLUMN status ENUM('active_client','active_internal','unassigned','inactive','suspended','deactivated','lost','faulty') NOT NULL DEFAULT 'unassigned'");

        // Seed the technicians list from the names already typed into devices
        // and the Asset Register (clean up the list on the Technicians screen).
        $names = collect(DB::table('adm_gps_devices')->whereNotNull('technician')->pluck('technician'))
            ->merge(DB::table('adm_asset_register')->whereNotNull('technician')->pluck('technician'))
            ->map(fn ($n) => trim(preg_replace('/\s+/', ' ', (string) $n)))
            ->filter()
            ->unique(fn ($n) => mb_strtolower($n))
            ->values();

        foreach ($names as $name) {
            DB::table('adm_technicians')->insertOrIgnore([
                'name'       => mb_substr($name, 0, 100),
                'is_active'  => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        foreach (DB::table('adm_technicians')->get() as $tech) {
            DB::table('adm_gps_devices')
                ->whereRaw('LOWER(TRIM(technician)) = ?', [mb_strtolower($tech->name)])
                ->update(['technician_id' => $tech->id]);
        }
    }

    public function down(): void
    {
        DB::table('adm_gps_devices')->whereIn('status', ['with_technician', 'faulty'])->update(['status' => 'in_office_stock']);
        DB::table('adm_sim_cards')->where('status', 'faulty')->update(['status' => 'unassigned']);

        DB::statement("ALTER TABLE adm_gps_devices MODIFY COLUMN status ENUM('in_office_stock','installed','decommissioned','lost_stolen') NOT NULL DEFAULT 'in_office_stock'");
        DB::statement("ALTER TABLE adm_sim_cards MODIFY COLUMN status ENUM('active_client','active_internal','unassigned','inactive','suspended','deactivated','lost') NOT NULL DEFAULT 'unassigned'");

        Schema::table('adm_gps_devices', function (Blueprint $table) {
            $table->dropForeign(['technician_id']);
            $table->dropColumn(['technician_id', 'installation_location']);
        });
    }
};
