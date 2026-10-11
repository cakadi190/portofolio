<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Additive on purpose: production already holds clarifications, so the
     * columns are added without touching the original create migration.
     */
    public function up(): void
    {
        Schema::table('clarifications', function (Blueprint $table) {
            $table->dateTime('withdrawn_at')->nullable()->index()->after('updates');
            $table->text('withdrawal_note')->nullable()->after('withdrawn_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clarifications', function (Blueprint $table) {
            $table->dropIndex(['withdrawn_at']);
            $table->dropColumn(['withdrawn_at', 'withdrawal_note']);
        });
    }
};
