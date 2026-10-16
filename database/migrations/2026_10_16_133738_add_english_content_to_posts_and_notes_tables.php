<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * English version of the article text, next to the Indonesian columns. `posts` and `notes`
 * already hold production data, so the columns are added by a new migration instead of
 * editing the original `create_*` migrations. Empty means "not translated yet".
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['posts', 'notes'] as $table) {
            if (Schema::hasColumn($table, 'title_en')) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->string('title_en')->nullable();
                $blueprint->string('excerpt_en')->nullable();
                $blueprint->longText('content_en')->nullable();
            });
        }
    }

    public function down(): void
    {
        // Intentionally empty: the English text is user-written content and must survive a rollback.
    }
};
