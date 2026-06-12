<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cellulant_settings', function (Blueprint $table) {
            $table->id();
            $table->enum('environment', ['sandbox', 'production'])->default('sandbox');
            $table->string('sandbox_base_url')->nullable();
            $table->string('sandbox_username')->nullable();
            $table->text('sandbox_password')->nullable();
            $table->string('sandbox_counter_code')->nullable();
            $table->string('production_base_url')->nullable();
            $table->string('production_username')->nullable();
            $table->text('production_password')->nullable();
            $table->string('production_counter_code')->nullable();
            $table->string('initiate_payment_path')->default('/initiateMerchantPayment');
            $table->string('default_payer_client_code')->default('MTNUG');
            $table->string('airtel_payer_client_code')->default('AIRTELUG');
            $table->boolean('auto_detect_payer')->default(true);
            $table->string('country_code', 3)->default('UGA');
            $table->string('currency_code', 3)->default('UGX');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cellulant_settings');
    }
};
