<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            // The 'encrypted' cast wraps the raw value in a JSON envelope
            // (iv/value/mac/tag) then base64-encodes it — a ~32-char API
            // key becomes 250-300+ characters once encrypted. The original
            // column (sized for the plaintext key) was too narrow, causing
            // MySQL to silently truncate the encrypted payload and fail
            // the save with "Data too long for column". TEXT removes any
            // length ceiling worth worrying about going forward.
            $table->text('navixy_api_key')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('navixy_api_key', 255)->nullable()->change();
        });
    }
};