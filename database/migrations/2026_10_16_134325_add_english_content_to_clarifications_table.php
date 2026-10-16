<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * English version of the clarification text, next to the Indonesian columns. The table
 * already holds production data, so the columns are added by a new migration. The
 * `sources` and `updates` lists stay as written (not translated).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('clarifications', 'title_en')) {
            return;
        }

        Schema::table('clarifications', function (Blueprint $table) {
            $table->string('title_en')->nullable();
            $table->text('claim_en')->nullable();
            $table->string('claim_origin_en')->nullable();
            $table->string('summary_en')->nullable();
            $table->longText('content_en')->nullable();
            $table->text('apology_en')->nullable();
            $table->text('withdrawal_note_en')->nullable();
        });
    }

    public function down(): void
    {
        // Intentionally empty: the English text is user-written content and must survive a rollback.
    }
};
