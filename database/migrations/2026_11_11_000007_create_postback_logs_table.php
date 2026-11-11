<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('postback_logs', function (Blueprint $table) {
            $table->id();
            $table->string('txn_id')->nullable()->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('offer_id')->nullable();
            $table->decimal('payout', 12, 4)->default(0);
            $table->string('status', 16)->default('credited')->index(); // credited | duplicate | rejected
            $table->text('payload')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('postback_logs');
    }
};
