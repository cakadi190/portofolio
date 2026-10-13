<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `create_posts_table` gained `softDeletes()` after it had already run in
 * production; this brings those databases in line. Fresh installs already
 * have the column, hence the guard.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('posts', 'deleted_at')) {
            return;
        }

        Schema::table('posts', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        // Intentionally empty: `create_posts_table` owns this column.
    }
};
