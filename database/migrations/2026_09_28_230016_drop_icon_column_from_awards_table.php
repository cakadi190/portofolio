<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Award icons are derived from rank/title, so nothing is stored anymore.
     */
    public function up(): void
    {
        Schema::table('awards', function (Blueprint $table) {
            $table->dropColumn('icon');
        });
    }

    public function down(): void
    {
        Schema::table('awards', function (Blueprint $table) {
            $table->string('icon')->nullable()->after('title');
        });
    }
};
