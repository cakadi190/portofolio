<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Iconify id (`prefix:name`) chosen from the icon search. `technologies` already holds
 * production data, so the column is added by a new migration; rows without one keep
 * resolving their icon from the name.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('technologies', 'icon')) {
            return;
        }

        Schema::table('technologies', function (Blueprint $table) {
            $table->string('icon')->nullable()->after('name');
        });
    }

    public function down(): void
    {
        // Intentionally empty: the chosen icon is part of the portfolio's content.
    }
};
