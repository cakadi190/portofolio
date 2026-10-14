<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Public URLs for categories, tags and authors (`/blog/categories/{slug}` …).
 * These tables already hold production data, so the slug is added by a new
 * migration and back-filled from the name before it becomes unique.
 */
return new class extends Migration
{
    /**
     * @var list<string>
     */
    private array $tables = ['post_categories', 'tags', 'users'];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            if (Schema::hasColumn($table, 'slug')) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->string('slug')->nullable()->after('name');
            });

            $this->backfill($table);

            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->unique('slug');
            });
        }
    }

    public function down(): void
    {
        // Intentionally empty: the slug is public URL data and must survive a rollback.
    }

    private function backfill(string $table): void
    {
        $taken = [];

        foreach (DB::table($table)->orderBy('id')->get(['id', 'name']) as $row) {
            $base = Str::slug((string) $row->name) ?: 'item';
            $slug = $base;

            for ($suffix = 1; isset($taken[$slug]); $suffix++) {
                $slug = "{$base}-{$suffix}";
            }

            $taken[$slug] = true;
            DB::table($table)->where('id', $row->id)->update(['slug' => $slug]);
        }
    }
};
