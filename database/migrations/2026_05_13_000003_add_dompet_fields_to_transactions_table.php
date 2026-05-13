<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE transactions MODIFY type ENUM('debit', 'credit', 'income', 'expense') NOT NULL");
            DB::table('transactions')->where('type', 'debit')->update(['type' => 'expense']);
            DB::table('transactions')->where('type', 'credit')->update(['type' => 'income']);
            DB::statement("ALTER TABLE transactions MODIFY type ENUM('income', 'expense') NOT NULL");
        }

        Schema::table('transactions', function (Blueprint $table) {
            if (! Schema::hasColumn('transactions', 'category_id')) {
                $table->foreignId('category_id')
                    ->nullable()
                    ->after('wallet_id')
                    ->constrained('categories')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('transactions', 'source')) {
                $table->enum('source', ['manual', 'chat'])->default('manual')->after('description');
            }

            if (! Schema::hasColumn('transactions', 'raw_message')) {
                $table->text('raw_message')->nullable()->after('source');
            }

            $table->index('category_id');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            if (Schema::hasColumn('transactions', 'category_id')) {
                $table->dropForeign(['category_id']);
                $table->dropColumn('category_id');
            }

            if (Schema::hasColumn('transactions', 'source')) {
                $table->dropColumn('source');
            }

            if (Schema::hasColumn('transactions', 'raw_message')) {
                $table->dropColumn('raw_message');
            }
        });
    }
};
