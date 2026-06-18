<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::create('users', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('email')->nullable();
        $table->unsignedBigInteger('navixy_user_id')->unique();
        $table->unsignedBigInteger('navixy_account_id');
        $table->enum('role', [
            'bt_admin', 'bt_technician', 'bt_support',
            'client_manager', 'client_viewer'
        ]);
        $table->boolean('is_active')->default(true);
        $table->rememberToken();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
