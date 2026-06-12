<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('machine_order_id', 100);
            $table->string('third_party_order_id', 100)->unique();
            $table->string('machine_id', 20);
            $table->string('track_no', 10)->nullable();
            $table->string('product_name');
            $table->unsignedBigInteger('amount');
            $table->string('channel_id', 10)->nullable();
            $table->enum('payment_status', ['pending', 'paid', 'failed', 'expired', 'refunded'])->default('pending');
            $table->enum('dispense_status', ['pending', 'success', 'failed'])->default('pending');
            $table->string('customer_phone', 20)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->unique(['machine_order_id', 'machine_id']);
            $table->index(['machine_id', 'payment_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
