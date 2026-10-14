<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Public author biography (sanitized WYSIWYG HTML). `users` already holds
 * production data, so the column is added by a new migration instead of
 * editing the original `create_users_table`.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'bio')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->text('bio')->nullable()->after('avatar');
        });
    }

    public function down(): void
    {
        // Intentionally empty: the biography is user-written content and must survive a rollback.
    }
};
