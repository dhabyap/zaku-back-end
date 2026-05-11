<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['debit', 'credit']);
            $table->decimal('amount', 15, 2);
            $table->text('description');
            $table->enum('status', ['pending', 'completed', 'failed'])->default('pending');
            $table->dateTime('transaction_date')->index();
            $table->string('reference_id')->nullable();
            $table->timestamps();

            $table->index('wallet_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
