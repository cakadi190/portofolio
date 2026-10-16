<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The language the visitor wrote in, so the confirmation and the admin's reply are
 * emailed in it. `contact_messages` already holds production data, so the column is
 * added by a new migration; existing messages count as Indonesian.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('contact_messages', 'locale')) {
            return;
        }

        Schema::table('contact_messages', function (Blueprint $table) {
            $table->string('locale', 5)->nullable();
        });
    }

    public function down(): void
    {
        // Intentionally empty: the preference is part of the visitor's message.
    }
};
