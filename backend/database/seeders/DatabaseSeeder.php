<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            // ── Core / structural defaults (always run on fresh install) ──
            UserSeeder::class,
            PermissionSeeder::class,
            SystemSettingsSeeder::class,
            HRSeeder::class,                  // departments + designations + leave types
            ProcurementDefaultsSeeder::class, // vendor categories
            ProjectDefaultsSeeder::class,     // budget categories

            // ── Demo / fixture data (kept for dev convenience) ──
            // These add named fixtures (vendors, projects, employees,
            // notes, demo notices) on top of the structural defaults
            // above. ResetDataCommand intentionally skips them.
            NoteSeeder::class,
            ProcurementSeeder::class,
            ProjectSeeder::class,
            CommunicationSeeder::class,
        ]);
    }
}
