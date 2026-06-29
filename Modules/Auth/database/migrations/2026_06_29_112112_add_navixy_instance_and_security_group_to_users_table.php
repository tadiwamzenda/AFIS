<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedTinyInteger('navixy_instance')->default(1)->after('navixy_user_id');
            $table->unsignedBigInteger('navixy_security_group_id')->nullable()->after('navixy_instance');
            $table->string('email')->nullable()->change();
            $table->index(['navixy_user_id', 'navixy_instance']);
        });

        // Add 'client' role — update existing client_manager/client_viewer to 'client'
        DB::statement("UPDATE users SET role = 'client' WHERE role IN ('client_manager', 'client_viewer')");

        // Modify enum to include 'client' and remove old client roles
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('bt_admin','bt_technician','bt_support','client') NOT NULL");
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['navixy_instance', 'navixy_security_group_id']);
        });
    }
};