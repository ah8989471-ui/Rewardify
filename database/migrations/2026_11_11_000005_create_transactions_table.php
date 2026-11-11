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
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 8)->index();   // credit | debit
            // ad_view | offerwall | referral_commission | withdrawal |
            // withdrawal_refund | manual_adjust | bonus
            $table->string('kind', 32)->index();
            $table->decimal('amount', 14, 4);
            $table->decimal('balance_after', 14, 4);
            $table->string('description')->nullable();
            $table->string('reference')->nullable()->index(); // token / txn id / withdrawal id
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
