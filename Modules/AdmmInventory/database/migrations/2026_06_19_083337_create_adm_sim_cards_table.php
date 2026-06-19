<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('adm_sim_cards', function (Blueprint $table) {
            $table->id();
            $table->string('iccid')->unique();
            $table->string('msisdn', 20)->nullable();
            $table->string('network_provider', 100);
            $table->string('bundle_type', 100)->nullable();
            $table->date('bundle_renewal_date')->nullable();
            $table->enum('status', [
                'active_client',
                'active_internal',
                'unassigned',
                'inactive',
                'suspended',
                'deactivated',
                'lost',
            ])->default('unassigned');
            $table->enum('location_context', [
                'client_assigned',
                'internal_stock',
                'unallocated',
            ])->default('unallocated');
            $table->foreignId('client_id')
                ->nullable()
                ->constrained('clients')
                ->nullOnDelete();
            $table->unsignedBigInteger('navixy_tracker_id')->nullable()->index();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('location_context');
            $table->index('bundle_renewal_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('adm_sim_cards');
    }
};