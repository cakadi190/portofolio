<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The public author handle (`/blog/@{username}`) was introduced as `users.slug`.
 * `users` already holds production data, so the column is renamed in place
 * (values and the unique index are kept) instead of being rebuilt.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'slug') || Schema::hasColumn('users', 'username')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('slug', 'username');
        });

        if (Schema::hasIndex('users', 'users_slug_unique')) {
            Schema::table('users', function (Blueprint $table) {
                $table->renameIndex('users_slug_unique', 'users_username_unique');
            });
        }
    }

    public function down(): void
    {
        // Intentionally empty: the username is public URL data and must survive a rollback.
    }
};
