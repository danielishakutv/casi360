<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Designation;
use Illuminate\Database\Seeder;

/**
 * Seeds the structural HR defaults every fresh CASI360 install needs:
 * a sensible set of departments (with stable `code` values that the
 * procurement approval workflow depends on) and the role-based
 * designations that hang off them.
 *
 * No named individuals here — demo employees live in
 * DemoEmployeesSeeder, which is opt-in and not called by the default
 * DatabaseSeeder. Production deployments and post-reset databases
 * therefore start clean: structure in place, no people fixtures.
 *
 * Idempotent — uses updateOrCreate keyed on `code` (departments) and
 * `(title, department_id)` (designations), so re-runs preserve any
 * super-admin tweaks to the descriptions / colors / heads.
 */
class HRSeeder extends Seeder
{
    public function run(): void
    {
        // ── Departments ──────────────────────────────────────────
        // The `code` is the workflow-critical identifier — keep these
        // stable. ApprovalAuthorizer routes Finance / Procurement
        // approvals using FINANCE and PROCUREMENT codes specifically.
        $departments = [
            ['code' => 'ADMIN',       'name' => 'Administration',           'description' => 'Executive office, governance, and corporate administration.', 'color' => '#6366F1'],
            ['code' => 'FINANCE',     'name' => 'Finance',                  'description' => 'Budgeting, accounting, payroll, and financial reporting.',    'color' => '#EC4899'],
            ['code' => 'PROCUREMENT', 'name' => 'Procurement',              'description' => 'Sourcing, vendor management, purchase orders, and supply.',   'color' => '#06B6D4'],
            ['code' => 'OPERATIONS',  'name' => 'Operations',               'description' => 'Day-to-day operational coordination across teams.',           'color' => '#14B8A6'],
            ['code' => 'PROGRAMS',    'name' => 'Programs',                 'description' => 'Program design, implementation, and beneficiary delivery.',    'color' => '#8B5CF6'],
            ['code' => 'HR',          'name' => 'Human Resources',          'description' => 'Recruitment, employee relations, and staff welfare.',         'color' => '#F97316'],
            ['code' => 'IT',          'name' => 'IT',                       'description' => 'Technology infrastructure, systems, and digital tools.',      'color' => '#3B82F6'],
            ['code' => 'LOGISTICS',   'name' => 'Logistics',                'description' => 'Warehousing, transport, and asset management.',                'color' => '#A855F7'],
            ['code' => 'MNE',         'name' => 'Monitoring & Evaluation',  'description' => 'Programme monitoring, learning, and impact evaluation.',      'color' => '#22C55E'],
            ['code' => 'COMMS',       'name' => 'Communications',           'description' => 'Public relations, media, and donor communications.',          'color' => '#EAB308'],
        ];

        $deptIdByCode = [];
        foreach ($departments as $dept) {
            $row = Department::updateOrCreate(
                ['code' => $dept['code']],
                array_merge($dept, ['head' => null, 'status' => 'active'])
            );
            $deptIdByCode[$dept['code']] = $row->id;
        }

        // ── Designations ─────────────────────────────────────────
        // Generic role-based titles per department. Levels: executive,
        // senior, mid, junior — used by HR for organisational charts.
        $designations = [
            // Administration
            ['title' => 'Executive Director',     'dept' => 'ADMIN',       'level' => 'executive', 'description' => 'Overall leadership and strategic direction.'],
            ['title' => 'Deputy Director',        'dept' => 'ADMIN',       'level' => 'executive', 'description' => 'Supports the Executive Director on organisation-wide operations.'],
            ['title' => 'Admin Officer',          'dept' => 'ADMIN',       'level' => 'mid',       'description' => 'Administrative support to the executive office.'],

            // Finance
            ['title' => 'Finance Manager',        'dept' => 'FINANCE',     'level' => 'senior',    'description' => 'Owns the budget cycle, financial reporting, and audit liaison.'],
            ['title' => 'Finance Officer',        'dept' => 'FINANCE',     'level' => 'mid',       'description' => 'Day-to-day financial operations and reconciliation.'],
            ['title' => 'Accountant',             'dept' => 'FINANCE',     'level' => 'mid',       'description' => 'Accounts payable, receivable, and ledger maintenance.'],
            ['title' => 'Account Assistant',      'dept' => 'FINANCE',     'level' => 'junior',    'description' => 'Supports finance operations with vouchers and reconciliations.'],

            // Procurement
            ['title' => 'Procurement Manager',    'dept' => 'PROCUREMENT', 'level' => 'senior',    'description' => 'Owns sourcing strategy, vendor governance, and PO sign-off.'],
            ['title' => 'Procurement Officer',    'dept' => 'PROCUREMENT', 'level' => 'mid',       'description' => 'Raises POs, runs RFQs, and manages vendor relationships.'],
            ['title' => 'Procurement Assistant',  'dept' => 'PROCUREMENT', 'level' => 'junior',    'description' => 'Supports procurement workflow and document handling.'],

            // Operations
            ['title' => 'Operations Manager',     'dept' => 'OPERATIONS',  'level' => 'senior',    'description' => 'Coordinates cross-team operational delivery.'],
            ['title' => 'Field Coordinator',      'dept' => 'OPERATIONS',  'level' => 'mid',       'description' => 'Coordinates field activities and on-the-ground teams.'],

            // Programs
            ['title' => 'Program Manager',        'dept' => 'PROGRAMS',    'level' => 'senior',    'description' => 'Owns program design, delivery, and donor reporting.'],
            ['title' => 'Program Officer',        'dept' => 'PROGRAMS',    'level' => 'mid',       'description' => 'Supports program implementation and beneficiary work.'],
            ['title' => 'Project Assistant',      'dept' => 'PROGRAMS',    'level' => 'junior',    'description' => 'Field-level support for project teams.'],

            // HR
            ['title' => 'HR Manager',             'dept' => 'HR',          'level' => 'senior',    'description' => 'Owns HR policy, recruitment, and employee relations.'],
            ['title' => 'HR Officer',             'dept' => 'HR',          'level' => 'mid',       'description' => 'Day-to-day HR operations and staff welfare.'],
            ['title' => 'Recruitment Officer',    'dept' => 'HR',          'level' => 'mid',       'description' => 'Talent acquisition and onboarding.'],

            // IT
            ['title' => 'IT Manager',             'dept' => 'IT',          'level' => 'senior',    'description' => 'Owns infrastructure, systems, and the IT roadmap.'],
            ['title' => 'IT Officer',             'dept' => 'IT',          'level' => 'mid',       'description' => 'Day-to-day systems administration and user support.'],
            ['title' => 'Software Developer',     'dept' => 'IT',          'level' => 'mid',       'description' => 'Develops and maintains software applications.'],
            ['title' => 'Data Analyst',           'dept' => 'IT',          'level' => 'mid',       'description' => 'Analyses organisational data to support decisions.'],

            // Logistics
            ['title' => 'Logistics Officer',      'dept' => 'LOGISTICS',   'level' => 'mid',       'description' => 'Manages transport, warehousing, and asset deployment.'],
            ['title' => 'Storekeeper',            'dept' => 'LOGISTICS',   'level' => 'junior',    'description' => 'Manages stores and stock records.'],
            ['title' => 'Driver',                 'dept' => 'LOGISTICS',   'level' => 'junior',    'description' => 'Vehicle handling and logistics support.'],

            // M&E
            ['title' => 'M&E Manager',            'dept' => 'MNE',         'level' => 'senior',    'description' => 'Owns monitoring, learning, and evaluation across programs.'],
            ['title' => 'M&E Officer',            'dept' => 'MNE',         'level' => 'mid',       'description' => 'Tracks indicators and conducts impact evaluations.'],

            // Communications
            ['title' => 'Communications Officer', 'dept' => 'COMMS',       'level' => 'mid',       'description' => 'Public relations, media, and donor communications.'],
        ];

        $created = 0;
        foreach ($designations as $desig) {
            $deptId = $deptIdByCode[$desig['dept']] ?? null;
            if (!$deptId) {
                continue;
            }
            Designation::updateOrCreate(
                ['title' => $desig['title'], 'department_id' => $deptId],
                [
                    'level'       => $desig['level'],
                    'description' => $desig['description'],
                    'status'      => 'active',
                ]
            );
            $created++;
        }

        $this->command?->info(
            'HR defaults seeded: ' . count($departments) . ' departments, ' . $created . ' designations.'
        );
    }
}
