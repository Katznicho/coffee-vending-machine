<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integration_logs', function (Blueprint $table) {
            $table->id();
            $table->enum('direction', ['inbound', 'outbound']);
            $table->string('channel', 40);
            $table->string('event', 60);
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('merchant_transaction_id')->nullable();
            $table->string('reference')->nullable();
            $table->string('machine_id', 20)->nullable();
            $table->string('http_method', 10)->nullable();
            $table->string('url', 500)->nullable();
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->boolean('success')->default(false);
            $table->unsignedInteger('duration_ms')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('message')->nullable();
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->timestamps();

            $table->index('created_at');
            $table->index(['channel', 'event']);
            $table->index(['direction', 'success']);
            $table->index('order_id');
            $table->index('merchant_transaction_id');
            $table->index('reference');
            $table->index('machine_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_logs');
    }
};
