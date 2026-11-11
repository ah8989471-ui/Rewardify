<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ads from networks like A-Ads come as an HTML embed code (iframe)
     * rather than a plain link. Tasks can now carry an embed code; the
     * plain URL becomes optional in that case.
     */
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->text('embed_code')->nullable()->after('url');
            $table->string('url', 2048)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn('embed_code');
            $table->string('url', 2048)->nullable(false)->change();
        });
    }
};
