<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('referral_code', 16)->unique()->nullable()->after('email');
            $table->foreignId('referred_by')->nullable()->after('referral_code')
                ->constrained('users')->nullOnDelete();
            $table->string('role', 16)->default('user')->index()->after('referred_by'); // user | admin
            $table->string('status', 16)->default('active')->index()->after('role');    // active | suspended
            $table->decimal('balance', 14, 4)->default(0)->after('status');
            $table->string('verification_code')->nullable()->after('balance');
            $table->timestamp('verification_code_expires_at')->nullable()->after('verification_code');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('referred_by');
            $table->dropColumn([
                'referral_code', 'role', 'status', 'balance',
                'verification_code', 'verification_code_expires_at',
            ]);
        });
    }
};
