<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

#[Signature('app:deploy-migrate-fresh {--seed : Seed the database after running migrations}')]
#[Description('Runs migrate:fresh in production. Only for the opt-in, clearly-labelled Jenkins deploy step against the not-yet-live colour — never call this by hand.')]
class DeployMigrateFresh extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (! app()->isProduction()) {
            $this->components->error('app:deploy-migrate-fresh is only meant to run in production; use migrate:fresh directly elsewhere.');

            return self::FAILURE;
        }

        // AppServiceProvider prohibits migrate:fresh (and other destructive
        // commands) in production on purpose. This command is the single,
        // narrowly-named exception to that guard, invoked only by the
        // Jenkins SHOULD_MIGRATE_FRESH step against the not-yet-live
        // colour — `migrate:fresh` itself stays blocked everywhere else.
        DB::prohibitDestructiveCommands(false);

        return Artisan::call('migrate:fresh', array_filter([
            '--force' => true,
            '--seed' => $this->option('seed'),
        ]), $this->output);
    }
}
