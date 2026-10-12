<?php

namespace App\Console\Commands;

use App\Services\Visitors\VisitorSettings;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Delete visitor identifiers, sessions and cached IP lookups older than the
 * retention window, in small batches so SQLite is never locked for long.
 * Aggregated daily statistics (no personal data) and audit logs are untouched.
 */
#[Signature('visitors:prune {--batch=500 : Rows deleted per statement} {--max-batches=200 : Upper bound of statements per table}')]
#[Description('Prune expired visitor data in bounded batches')]
class PruneVisitorData extends Command
{
    public function handle(VisitorSettings $settings): int
    {
        $cutoff = now()->subDays($settings->retentionDays());
        $batch = max(10, (int) $this->option('batch'));
        $max = max(1, (int) $this->option('max-batches'));

        $deleted = [
            'visitors' => $this->prune('visitors', 'last_seen_at', $cutoff, $batch, $max),
            'visits' => $this->prune('visits', 'last_activity_at', $cutoff, $batch, $max),
            'ip_lookups' => $this->prune('ip_lookups', 'expires_at', $cutoff, $batch, $max),
        ];

        foreach ($deleted as $table => $count) {
            $this->components->info("{$table}: {$count} baris dihapus");
        }

        return self::SUCCESS;
    }

    private function prune(string $table, string $column, \DateTimeInterface $cutoff, int $batch, int $max): int
    {
        $total = 0;

        for ($i = 0; $i < $max; $i++) {
            $ids = DB::table($table)->where($column, '<', $cutoff)->orderBy('id')->limit($batch)->pluck('id');

            if ($ids->isEmpty()) {
                break;
            }

            $total += DB::table($table)->whereIn('id', $ids)->delete();
            usleep(20_000);
        }

        return $total;
    }
}
