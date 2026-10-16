<?php

use App\Support\TranslationMigration;
use Illuminate\Database\Migrations\Migration;

/**
 * Post title, excerpt and content become translated columns. They keep their column
 * types (the JSON map is stored as text), so only existing rows need converting to
 * `{"id": "..."}`; the `create_posts_table` migration stays untouched.
 */
return new class extends Migration
{
    public function up(): void
    {
        TranslationMigration::wrap('posts', ['title', 'excerpt', 'content']);
    }

    public function down(): void
    {
        // Intentionally empty: translations are content and must survive a rollback.
    }
};
