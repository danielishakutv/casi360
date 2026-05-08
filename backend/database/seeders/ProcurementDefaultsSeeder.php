<?php

namespace Database\Seeders;

use App\Models\VendorCategory;
use Illuminate\Database\Seeder;

/**
 * Structural procurement defaults for fresh installs and post-reset.
 *
 * Seeds a sensible NGO-standard set of vendor categories so the
 * Vendors page is immediately useful — every vendor needs a category,
 * and admins shouldn't have to invent a vocabulary before they can
 * register their first supplier.
 *
 * Idempotent: keyed on `name`, so re-running preserves any
 * descriptions / status tweaks the super admin made via the UI.
 *
 * Demo vendors live in ProcurementSeeder (opt-in); this seeder is
 * structural only and runs by default.
 */
class ProcurementDefaultsSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Office Supplies & Stationery',  'description' => 'Pens, paper, printer cartridges, binders, and consumables.'],
            ['name' => 'IT Equipment & Software',       'description' => 'Computers, peripherals, networking gear, and software licences.'],
            ['name' => 'Construction & Renovation',     'description' => 'Building materials, contractors, and site works.'],
            ['name' => 'Professional Services',         'description' => 'Legal, audit, design, and other professional consultancies.'],
            ['name' => 'Travel & Hospitality',          'description' => 'Flights, hotels, ground transport, and travel agents.'],
            ['name' => 'Catering & Events',             'description' => 'Catering, venues, and event-management providers.'],
            ['name' => 'Printing & Publishing',         'description' => 'Print runs, signage, branded materials, and publications.'],
            ['name' => 'Medical Supplies',              'description' => 'Pharmaceuticals, PPE, and medical equipment for field programmes.'],
            ['name' => 'Cleaning & Janitorial',         'description' => 'Cleaning services and janitorial supplies.'],
            ['name' => 'Furniture & Fixtures',          'description' => 'Office furniture, fixtures, and fittings.'],
            ['name' => 'Vehicles & Transport',          'description' => 'Vehicles, vehicle hire, fuel, and maintenance.'],
            ['name' => 'Telecommunications',            'description' => 'Internet, mobile, and telephony services.'],
            ['name' => 'Utilities',                     'description' => 'Power, water, and other recurring utility providers.'],
            ['name' => 'Training & Development',        'description' => 'Trainers, facilitators, and capacity-building providers.'],
            ['name' => 'Other',                         'description' => 'Anything that doesn\'t fit the categories above.'],
        ];

        foreach ($categories as $cat) {
            VendorCategory::updateOrCreate(
                ['name' => $cat['name']],
                array_merge($cat, ['status' => 'active'])
            );
        }

        $this->command?->info('Procurement defaults seeded: ' . count($categories) . ' vendor categories.');
    }
}
