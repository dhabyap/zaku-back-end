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
        Schema::table('wallets', function (Blueprint $table) {
            $table->bigInteger('balance_cents')->default(0)->after('balance');
        });
        // Copy existing decimal balance to cents (multiply by 100)
        DB::statement('UPDATE wallets SET balance_cents = CAST(balance * 100 AS SIGNED)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wallets', function (Blueprint $table) {
            $table->dropColumn('balance_cents');
        });
    }
};
