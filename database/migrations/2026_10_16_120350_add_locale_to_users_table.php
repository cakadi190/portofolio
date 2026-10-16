<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Interface language of the admin/settings area. `users` already holds production
 * data, so the column is added by a new migration instead of editing `create_users_table`.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'locale')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('locale', 5)->nullable()->after('bio');
        });
    }

    public function down(): void
    {
        // Intentionally empty: the preference is user data and must survive a rollback.
    }
};
