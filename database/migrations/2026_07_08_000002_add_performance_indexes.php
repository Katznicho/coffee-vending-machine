<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->index('payment_status');
            $table->index('dispense_status');
            $table->index('created_at');
            $table->index('machine_order_id');
            $table->index('customer_phone');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->index('transaction_id');
            $table->index('status');
            // order_id is already indexed by the foreign key; a composite (order_id, status)
            // index would replace it and break rollback on MySQL.
            $table->index('created_at');
        });

        Schema::table('cellulant_ipn_logs', function (Blueprint $table) {
            $table->index('created_at');
            $table->index('order_matched');
            $table->index('status_code');
        });
    }

    public function down(): void
    {
        $this->dropIndexIfExists('orders', 'orders_payment_status_index');
        $this->dropIndexIfExists('orders', 'orders_dispense_status_index');
        $this->dropIndexIfExists('orders', 'orders_created_at_index');
        $this->dropIndexIfExists('orders', 'orders_machine_order_id_index');
        $this->dropIndexIfExists('orders', 'orders_customer_phone_index');

        $this->dropIndexIfExists('payments', 'payments_transaction_id_index');
        $this->dropIndexIfExists('payments', 'payments_status_index');
        $this->dropIndexIfExists('payments', 'payments_created_at_index');

        $this->dropLegacyPaymentsOrderStatusIndex();

        $this->dropIndexIfExists('cellulant_ipn_logs', 'cellulant_ipn_logs_created_at_index');
        $this->dropIndexIfExists('cellulant_ipn_logs', 'cellulant_ipn_logs_order_matched_index');
        $this->dropIndexIfExists('cellulant_ipn_logs', 'cellulant_ipn_logs_status_code_index');
    }

    protected function dropIndexIfExists(string $table, string $indexName): void
    {
        $exists = collect(DB::select(
            'SHOW INDEX FROM `'.$table.'` WHERE Key_name = ?',
            [$indexName]
        ))->isNotEmpty();

        if (! $exists) {
            return;
        }

        Schema::table($table, function (Blueprint $table) use ($indexName) {
            $table->dropIndex($indexName);
        });
    }

    /**
     * Earlier versions of this migration added a composite (order_id, status) index.
     * MySQL uses that index for the order_id foreign key, so it cannot be dropped
     * until a standalone order_id index exists.
     */
    protected function dropLegacyPaymentsOrderStatusIndex(): void
    {
        if (! $this->indexExists('payments', 'payments_order_id_status_index')) {
            return;
        }

        if (! $this->indexExists('payments', 'payments_order_id_index')
            && ! $this->indexExists('payments', 'payments_order_id_foreign')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->index('order_id');
            });
        }

        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['order_id', 'status']);
        });
    }

    protected function indexExists(string $table, string $indexName): bool
    {
        return collect(DB::select(
            'SHOW INDEX FROM `'.$table.'` WHERE Key_name = ?',
            [$indexName]
        ))->isNotEmpty();
    }
};
