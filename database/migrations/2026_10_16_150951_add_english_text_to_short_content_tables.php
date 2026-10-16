<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * English versions of the short public texts (event titles, award titles, positions,
 * descriptions, taxonomy names), next to the Indonesian columns. These tables already
 * hold production data, so the columns are added by a new migration.
 */
return new class extends Migration
{
    /**
     * @var array<string, array<string, string>>
     */
    private const COLUMNS = [
        'speaking_engagements' => ['title_en' => 'string', 'description_en' => 'text'],
        'awards' => ['title_en' => 'string'],
        'careers' => ['position_en' => 'string'],
        'organizations' => ['description_en' => 'text'],
        'coffee_places' => ['description_en' => 'text'],
        'dev_communities' => ['description_en' => 'text'],
        'programmer_channels' => ['description_en' => 'text'],
        'tags' => ['name_en' => 'string'],
        'post_categories' => ['name_en' => 'string'],
    ];

    public function up(): void
    {
        foreach (self::COLUMNS as $table => $columns) {
            foreach ($columns as $column => $type) {
                if (Schema::hasColumn($table, $column)) {
                    continue;
                }

                Schema::table($table, function (Blueprint $blueprint) use ($column, $type) {
                    $blueprint->{$type}($column)->nullable();
                });
            }
        }
    }

    public function down(): void
    {
        // Intentionally empty: the English text is user-written content and must survive a rollback.
    }
};
