<?php

namespace Database\Seeders;

use App\Models\BudgetCategory;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Project;
use App\Models\ProjectActivity;
use App\Models\ProjectBudgetLine;
use App\Models\ProjectDonor;
use App\Models\ProjectPartner;
use App\Models\ProjectTeamMember;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Budget Categories
        |--------------------------------------------------------------------------
        */
        $categories = [
            ['name' => 'Personnel', 'description' => 'Staff salaries, allowances, and benefits', 'sort_order' => 1],
            ['name' => 'Travel & Transport', 'description' => 'Travel costs, vehicle hire, fuel, and per diem', 'sort_order' => 2],
            ['name' => 'Equipment', 'description' => 'Office equipment, IT hardware, and furniture', 'sort_order' => 3],
            ['name' => 'Supplies & Materials', 'description' => 'Office supplies, training materials, and consumables', 'sort_order' => 4],
            ['name' => 'Contractual Services', 'description' => 'Consultants, subcontractors, and professional services', 'sort_order' => 5],
            ['name' => 'Training & Capacity Building', 'description' => 'Workshop costs, training fees, and venue hire', 'sort_order' => 6],
            ['name' => 'Monitoring & Evaluation', 'description' => 'M&E activities, surveys, and data collection', 'sort_order' => 7],
            ['name' => 'Communication', 'description' => 'Communication, visibility, and media costs', 'sort_order' => 8],
            ['name' => 'Other Direct Costs', 'description' => 'Miscellaneous direct project costs', 'sort_order' => 9],
            ['name' => 'Indirect / Admin Costs', 'description' => 'Overhead, administrative, and organizational costs', 'sort_order' => 10],
        ];

        $catMap = [];
        foreach ($categories as $cat) {
            $c = BudgetCategory::updateOrCreate(['name' => $cat['name']], $cat);
            $catMap[$cat['name']] = $c->id;
        }

        $this->command->info('Budget categories seeded: ' . count($categories));

        /*
        |--------------------------------------------------------------------------
        | Sample Projects
        |--------------------------------------------------------------------------
        */
        $department = Department::where('status', 'active')->first();
        $employees = Employee::where('status', 'active')->limit(5)->get();

        if (!$department || $employees->isEmpty()) {
            $this->command->warn('Skipping project seed — no active departments or employees found.');
            return;
        }

        // Project 1: Community Health Initiative
        $project1 = Project::updateOrCreate(
            ['project_code' => 'PRJ-202601-0001'],
            [
                'name' => 'Community Health Outreach Program',
                'description' => 'Comprehensive community health outreach program targeting underserved areas in Lagos State. The program provides free medical consultations, health education, and essential medication distribution.',
                'objectives' => "1. Provide free health screenings to 5,000 community members\n2. Train 50 community health volunteers\n3. Distribute essential medications to 2,000 households\n4. Conduct 20 health education workshops",
                'department_id' => $department->id,
                'project_manager_id' => $employees->first()->id,
                'start_date' => '2026-01-15',
                'end_date' => '2026-12-31',
                'location' => 'Lagos State, Nigeria',
                'currency' => 'NGN',
                'status' => 'active',
                'notes' => 'Flagship community health project for 2026.',
            ]
        );

        // Donors
        ProjectDonor::updateOrCreate(
            ['project_id' => $project1->id, 'name' => 'Global Health Fund'],
            ['type' => 'multilateral', 'email' => 'grants@ghf.org', 'contribution_amount' => 15000000, 'notes' => 'Primary donor']
        );
        ProjectDonor::updateOrCreate(
            ['project_id' => $project1->id, 'name' => 'Lagos State Government'],
            ['type' => 'government', 'email' => 'ministry@lagosstate.gov.ng', 'contribution_amount' => 5000000, 'notes' => 'Co-funding partner']
        );

        // Partners
        ProjectPartner::updateOrCreate(
            ['project_id' => $project1->id, 'name' => 'Health Access Foundation'],
            ['role' => 'implementing', 'contact_person' => 'Dr. Amina Yusuf', 'email' => 'amina@haf.org', 'phone' => '+234 800 111 2222']
        );
        ProjectPartner::updateOrCreate(
            ['project_id' => $project1->id, 'name' => 'MedSupply Nigeria'],
            ['role' => 'logistics', 'contact_person' => 'Chinedu Okoro', 'email' => 'chinedu@medsupply.ng', 'phone' => '+234 800 333 4444']
        );

        // Team Members
        if ($employees->count() >= 3) {
            ProjectTeamMember::updateOrCreate(
                ['project_id' => $project1->id, 'employee_id' => $employees[0]->id],
                ['role' => 'project_manager', 'start_date' => '2026-01-15']
            );
            ProjectTeamMember::updateOrCreate(
                ['project_id' => $project1->id, 'employee_id' => $employees[1]->id],
                ['role' => 'coordinator', 'start_date' => '2026-01-15']
            );
            ProjectTeamMember::updateOrCreate(
                ['project_id' => $project1->id, 'employee_id' => $employees[2]->id],
                ['role' => 'm_and_e', 'start_date' => '2026-02-01']
            );
        }

        // Activities
        $activities = [
            ['title' => 'Community Needs Assessment', 'status' => 'completed', 'completion_percentage' => 100, 'target_date' => '2026-02-28', 'start_date' => '2026-01-15', 'end_date' => '2026-02-20', 'sort_order' => 1],
            ['title' => 'Volunteer Recruitment & Training', 'status' => 'completed', 'completion_percentage' => 100, 'target_date' => '2026-03-31', 'start_date' => '2026-02-01', 'end_date' => '2026-03-25', 'sort_order' => 2],
            ['title' => 'Health Screening Campaign - Phase 1', 'status' => 'in_progress', 'completion_percentage' => 60, 'target_date' => '2026-06-30', 'start_date' => '2026-04-01', 'sort_order' => 3],
            ['title' => 'Medication Distribution', 'status' => 'not_started', 'completion_percentage' => 0, 'target_date' => '2026-08-31', 'sort_order' => 4],
            ['title' => 'Health Education Workshops', 'status' => 'in_progress', 'completion_percentage' => 30, 'target_date' => '2026-11-30', 'start_date' => '2026-03-01', 'sort_order' => 5],
            ['title' => 'Final Evaluation & Reporting', 'status' => 'not_started', 'completion_percentage' => 0, 'target_date' => '2026-12-31', 'sort_order' => 6],
        ];

        foreach ($activities as $act) {
            ProjectActivity::updateOrCreate(
                ['project_id' => $project1->id, 'title' => $act['title']],
                $act
            );
        }

        // Budget Lines
        $budgetLines = [
            ['category' => 'Personnel', 'description' => 'Project Manager (12 months)', 'unit' => 'month', 'quantity' => 12, 'unit_cost' => 500000],
            ['category' => 'Personnel', 'description' => 'Project Coordinator (12 months)', 'unit' => 'month', 'quantity' => 12, 'unit_cost' => 350000],
            ['category' => 'Personnel', 'description' => 'M&E Officer (10 months)', 'unit' => 'month', 'quantity' => 10, 'unit_cost' => 300000],
            ['category' => 'Travel & Transport', 'description' => 'Field vehicle hire', 'unit' => 'trip', 'quantity' => 40, 'unit_cost' => 25000],
            ['category' => 'Travel & Transport', 'description' => 'Staff per diem (field visits)', 'unit' => 'day', 'quantity' => 120, 'unit_cost' => 5000],
            ['category' => 'Equipment', 'description' => 'Blood pressure monitors', 'unit' => 'unit', 'quantity' => 20, 'unit_cost' => 35000],
            ['category' => 'Equipment', 'description' => 'Glucose testing kits', 'unit' => 'unit', 'quantity' => 20, 'unit_cost' => 25000],
            ['category' => 'Supplies & Materials', 'description' => 'Essential medications', 'unit' => 'pack', 'quantity' => 2000, 'unit_cost' => 1500],
            ['category' => 'Supplies & Materials', 'description' => 'Health education materials', 'unit' => 'set', 'quantity' => 500, 'unit_cost' => 800],
            ['category' => 'Training & Capacity Building', 'description' => 'Volunteer training workshop (5 days)', 'unit' => 'workshop', 'quantity' => 2, 'unit_cost' => 500000],
            ['category' => 'Monitoring & Evaluation', 'description' => 'Baseline survey', 'unit' => 'lump_sum', 'quantity' => 1, 'unit_cost' => 750000],
            ['category' => 'Monitoring & Evaluation', 'description' => 'End-line evaluation', 'unit' => 'lump_sum', 'quantity' => 1, 'unit_cost' => 750000],
            ['category' => 'Communication', 'description' => 'Community mobilization & visibility', 'unit' => 'lump_sum', 'quantity' => 1, 'unit_cost' => 400000],
            ['category' => 'Indirect / Admin Costs', 'description' => 'Administrative overhead (7%)', 'unit' => 'lump_sum', 'quantity' => 1, 'unit_cost' => 1050000],
        ];

        foreach ($budgetLines as $bl) {
            ProjectBudgetLine::updateOrCreate(
                ['project_id' => $project1->id, 'description' => $bl['description']],
                [
                    'budget_category_id' => $catMap[$bl['category']],
                    'unit' => $bl['unit'],
                    'quantity' => $bl['quantity'],
                    'unit_cost' => $bl['unit_cost'],
                    'total_cost' => round($bl['quantity'] * $bl['unit_cost'], 2),
                ]
            );
        }

        $project1->recalculateTotalBudget();

        // Project 2: Education Support (draft)
        $project2 = Project::updateOrCreate(
            ['project_code' => 'PRJ-202603-0001'],
            [
                'name' => 'Rural Education Support Initiative',
                'description' => 'Providing educational support to rural schools including teacher training, learning materials, and infrastructure improvements.',
                'objectives' => "1. Train 100 teachers in modern pedagogy\n2. Equip 15 schools with learning materials\n3. Renovate 5 school buildings",
                'department_id' => $department->id,
                'project_manager_id' => $employees->count() >= 4 ? $employees[3]->id : $employees->first()->id,
                'start_date' => '2026-06-01',
                'end_date' => '2027-05-31',
                'location' => 'Ogun State, Nigeria',
                'currency' => 'NGN',
                'status' => 'draft',
                'notes' => 'Pending donor confirmation.',
            ]
        );

        ProjectDonor::updateOrCreate(
            ['project_id' => $project2->id, 'name' => 'UNICEF Nigeria'],
            ['type' => 'multilateral', 'email' => 'nigeria@unicef.org', 'contribution_amount' => 25000000]
        );

        $this->command->info('Projects seeded: 2 projects with donors, partners, team, activities, and budget lines.');
    }
}
