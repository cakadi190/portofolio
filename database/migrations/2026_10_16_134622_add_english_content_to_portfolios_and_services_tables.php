<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * English version of the portfolio and service text, next to the Indonesian columns. Both
 * tables already hold production data, so the columns are added by a new migration.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('portfolios', 'name_en')) {
            Schema::table('portfolios', function (Blueprint $table) {
                $table->string('name_en')->nullable();
                $table->text('short_desc_en')->nullable();
                $table->longText('description_en')->nullable();
            });
        }

        if (! Schema::hasColumn('services', 'name_en')) {
            Schema::table('services', function (Blueprint $table) {
                $table->string('name_en')->nullable();
                $table->longText('description_en')->nullable();
            });
        }
    }

    public function down(): void
    {
        // Intentionally empty: the English text is user-written content and must survive a rollback.
    }
};
