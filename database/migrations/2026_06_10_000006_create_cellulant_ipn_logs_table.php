<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cellulant_ipn_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('merchant_transaction_id')->nullable()->index();
            $table->string('reference')->nullable()->index();
            $table->string('status_code', 20)->nullable();
            $table->string('msisdn', 20)->nullable();
            $table->unsignedBigInteger('amount')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->json('request_payload');
            $table->json('response_payload');
            $table->boolean('order_matched')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cellulant_ipn_logs');
    }
};
