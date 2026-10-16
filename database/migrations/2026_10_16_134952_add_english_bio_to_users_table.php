<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * English author biography, next to `bio`. `users` already holds production data, so the
 * column is added by a new migration instead of editing `create_users_table`.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'bio_en')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->text('bio_en')->nullable()->after('bio');
        });
    }

    public function down(): void
    {
        // Intentionally empty: the biography is user-written content and must survive a rollback.
    }
};
