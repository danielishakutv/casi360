<?php

namespace App\Console\Commands;

use Database\Seeders\DemoDataSeeder;
use Illuminate\Console\Command;

/**
 * Top up an existing database with two of every record-type so the UI
 * has data on every screen for trial/demo runs.
 *
 * Adds (idempotently — safe to re-run):
 *   - 8 demo users covering every role (password Demo1234!)
 *   - 2 vendors
 *   - 2 projects with team members + budget lines
 *   - 2 of each procurement form (BOQ, PR, RFQ, PO, GRN, Invoice, RFP),
 *     wired together so the document chain shows real linkages
 *
 * Existing data is never modified or deleted — every demo row is keyed
 * on a stable "DEMO" name/number, so re-running the command is a no-op
 * for rows that already exist.
 *
 * Refuses to run in production without --force (or interactive
 * confirmation) so it can't be triggered by accident on real data.
 *
 * Usage:
 *   php artisan demo:seed
 *   php artisan demo:seed --force
 */
class SeedDemoDataCommand extends Command
{
    protected $signature = 'demo:seed {--force : Skip the production confirmation prompt}';

    protected $description = 'Add 2-of-everything demo data (users, vendors, projects, full procurement chain). Idempotent — safe to re-run.';

    public function handle(DemoDataSeeder $seeder): int
    {
        if (app()->environment('production') && !$this->option('force')) {
            $this->warn('You are about to seed demo data into a PRODUCTION database.');
            $this->line('Existing rows are preserved — this only ADDS records keyed on the "DEMO" prefix.');
            if (!$this->confirm('Continue?', false)) {
                $this->info('Aborted.');
                return self::SUCCESS;
            }
        }

        $this->info('Seeding demo data…');
        $seeder->setCommand($this);
        $seeder->run();
        $this->info('Done.');

        return self::SUCCESS;
    }
}
