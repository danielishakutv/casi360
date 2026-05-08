<?php

namespace Database\Seeders;

use App\Models\BudgetCategory;
use Illuminate\Database\Seeder;

/**
 * Structural project defaults for fresh installs and post-reset.
 *
 * Seeds the budget categories that PR / PO line items, project budget
 * lines, and RFP forms reference. Without them the budget-line
 * dropdowns are blank and the financial coding system is incomplete.
 *
 * The category names + ordering match the ones the procurement and
 * RFP forms hardcode, so making them DB defaults unifies the source
 * of truth — when admins add a new category here, it propagates
 * through every dropdown that reads from this table.
 *
 * Idempotent: keyed on `name`. Re-running preserves admin tweaks.
 *
 * Demo projects live in ProjectSeeder (opt-in); this seeder is
 * structural only and runs by default.
 */
class ProjectDefaultsSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Staff Costs',                  'description' => 'Salaries, allowances, benefits, and per-diems.',                              'sort_order' => 1],
            ['name' => 'Travel & Transport',           'description' => 'Flights, ground transport, vehicle hire, fuel, and accommodation.',           'sort_order' => 2],
            ['name' => 'Equipment & Supplies',         'description' => 'Office equipment, IT hardware, furniture, and operational supplies.',        'sort_order' => 3],
            ['name' => 'Office Costs',                 'description' => 'Rent, utilities, internet, telephony, and routine office expenses.',          'sort_order' => 4],
            ['name' => 'Training & Capacity Building', 'description' => 'Trainers, workshops, and capacity-building activities.',                       'sort_order' => 5],
            ['name' => 'Communication',                'description' => 'Printing, design, advocacy materials, and external communications.',          'sort_order' => 6],
            ['name' => 'Construction & Renovation',    'description' => 'Building materials, contractors, and site rehabilitation work.',              'sort_order' => 7],
            ['name' => 'Monitoring & Evaluation',      'description' => 'Surveys, evaluations, M&E consultants, and reporting.',                       'sort_order' => 8],
            ['name' => 'Professional Services',        'description' => 'Consultancies, legal, audit, and other professional fees.',                   'sort_order' => 9],
            ['name' => 'Other Direct Costs',           'description' => 'Direct programme costs that don\'t fit the categories above.',                'sort_order' => 10],
        ];

        foreach ($categories as $cat) {
            BudgetCategory::updateOrCreate(
                ['name' => $cat['name']],
                array_merge($cat, ['status' => 'active'])
            );
        }

        $this->command?->info('Project defaults seeded: ' . count($categories) . ' budget categories.');
    }
}
