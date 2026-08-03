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
        Schema::table('transactions', function (Blueprint $table) {
            if (! Schema::hasIndex('transactions', 'transactions_wallet_id_status_transaction_date_index')) {
                $table->index(['wallet_id', 'status', 'transaction_date'], 'transactions_wallet_id_status_transaction_date_index');
            }
            if (! Schema::hasIndex('transactions', 'transactions_wallet_id_type_transaction_date_index')) {
                $table->index(['wallet_id', 'type', 'transaction_date'], 'transactions_wallet_id_type_transaction_date_index');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex(['wallet_id', 'status', 'transaction_date']);
            $table->dropIndex(['wallet_id', 'type', 'transaction_date']);
        });
    }
};
