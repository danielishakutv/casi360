<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            PermissionSeeder::class,
            HRSeeder::class,
            NoteSeeder::class,
            SystemSettingsSeeder::class,
            ProcurementSeeder::class,
            ProjectSeeder::class,
            CommunicationSeeder::class,
        ]);
    }
}
