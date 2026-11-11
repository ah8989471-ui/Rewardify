<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ad_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->string('token', 64)->unique();
            $table->string('status', 16)->default('started')->index(); // started | completed | expired
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->decimal('reward_credited', 12, 4)->default(0);
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'task_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_views');
    }
};
