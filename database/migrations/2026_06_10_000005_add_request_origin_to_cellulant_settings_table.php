<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cellulant_settings', function (Blueprint $table) {
            $table->string('request_origin_code')->default('TINGG_INSTORE_INTEGRATION')->after('currency_code');
            $table->string('oauth_scope')->default('read')->after('request_origin_code');
        });
    }

    public function down(): void
    {
        Schema::table('cellulant_settings', function (Blueprint $table) {
            $table->dropColumn(['request_origin_code', 'oauth_scope']);
        });
    }
};
