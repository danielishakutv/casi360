<?php

namespace Database\Seeders;

use App\Models\ApprovalStep;
use App\Models\Boq;
use App\Models\BoqItem;
use App\Models\BudgetCategory;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\Grn;
use App\Models\GrnItem;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\ProjectBudgetLine;
use App\Models\ProjectTeamMember;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Requisition;
use App\Models\RequisitionItem;
use App\Models\Rfp;
use App\Models\RfpItem;
use App\Models\Rfq;
use App\Models\RfqItem;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Idempotent "trial" data: two of every record-type across HR, Projects,
 * Vendors, and the full procurement chain (BOQ → PR → RFQ → PO → GRN →
 * Invoice → RFP), wired together so the chain UI shows real linkages.
 *
 * Re-running the seeder is safe — every row is keyed on a stable
 * "DEMO " name / number, so existing rows are reused rather than
 * duplicated and existing non-demo data is never touched.
 *
 * Run via:
 *   php artisan demo:seed
 *
 * All demo users share the password: Demo1234!
 */
class DemoDataSeeder extends Seeder
{
    private const DEMO_PASSWORD = 'Demo1234!';

    /** @var array<string, User> */
    private array $users = [];

    /** @var array<string, Vendor> */
    private array $vendors = [];

    /** @var array<string, Project> */
    private array $projects = [];

    public function run(): void
    {
        DB::transaction(function () {
            $this->seedUsers();
            $this->seedVendors();
            $this->seedProjects();

            // Procurement chain — each stage references the previous so
            // the document chain UI shows real linkages end-to-end.
            $boqs = $this->seedBoqs();
            $prs  = $this->seedPRs($boqs);
            $rfqs = $this->seedRfqs($prs);
            $pos  = $this->seedPOs($prs, $rfqs);
            $grns = $this->seedGrns($pos);
            $inv  = $this->seedInvoices($pos);
            $this->seedRfps($inv);
        });

        $this->command?->info('Demo data seeded.');
        $this->command?->line('  Users: 8 (password for all: ' . self::DEMO_PASSWORD . ')');
        $this->command?->line('  Vendors: 2');
        $this->command?->line('  Projects: 2 (with team + budget lines)');
        $this->command?->line('  Procurement chain: 2 each of BOQ / PR / RFQ / PO / GRN / Invoice / RFP');
    }

    /* ================================================================
     * 1. Users (covers every role) — auto-create their Employee row
     * ================================================================ */
    private function seedUsers(): void
    {
        $deptIdByName = Department::pluck('id', 'name')->toArray();

        $users = [
            ['key' => 'super1',   'name' => 'DEMO Super Admin One',         'email' => 'demo.super1@demo.casi.org',   'role' => 'super_admin', 'department' => null,             'phone' => '+234 800 000 0001'],
            ['key' => 'super2',   'name' => 'DEMO Super Admin Two',         'email' => 'demo.super2@demo.casi.org',   'role' => 'super_admin', 'department' => null,             'phone' => '+234 800 000 0002'],
            ['key' => 'admin1',   'name' => 'DEMO Admin One',               'email' => 'demo.admin1@demo.casi.org',   'role' => 'admin',       'department' => 'Administration', 'phone' => '+234 800 000 0003'],
            ['key' => 'admin2',   'name' => 'DEMO Admin Two',               'email' => 'demo.admin2@demo.casi.org',   'role' => 'admin',       'department' => 'Administration', 'phone' => '+234 800 000 0004'],
            ['key' => 'procmgr',  'name' => 'DEMO Procurement Manager',     'email' => 'demo.procmgr@demo.casi.org',  'role' => 'manager',     'department' => 'Procurement',    'phone' => '+234 800 000 0005'],
            ['key' => 'finmgr',   'name' => 'DEMO Finance Manager',         'email' => 'demo.finmgr@demo.casi.org',   'role' => 'manager',     'department' => 'Finance',        'phone' => '+234 800 000 0006'],
            ['key' => 'opsstaff', 'name' => 'DEMO Operations Officer',      'email' => 'demo.opsstaff@demo.casi.org', 'role' => 'staff',       'department' => 'Operations',     'phone' => '+234 800 000 0007'],
            ['key' => 'logstaff', 'name' => 'DEMO Logistics Officer',       'email' => 'demo.logstaff@demo.casi.org', 'role' => 'staff',       'department' => 'Logistics',      'phone' => '+234 800 000 0008'],
        ];

        $hashedPassword = Hash::make(self::DEMO_PASSWORD);

        foreach ($users as $u) {
            $user = User::firstOrCreate(
                ['email' => $u['email']],
                [
                    'name'                  => $u['name'],
                    'password'              => $hashedPassword,
                    'role'                  => $u['role'],
                    'department'            => $u['department'],
                    'phone'                 => $u['phone'],
                    'status'                => 'active',
                    'email_verified_at'     => now(),
                    'force_password_change' => false,
                ]
            );

            // The User::booted() hook auto-creates an Employee row, but
            // it leaves department_id null. Fill it in here so the demo
            // user shows up under the right department in HR pickers.
            if ($u['department'] && $user->employee) {
                $deptId = $deptIdByName[$u['department']] ?? null;
                if ($deptId && !$user->employee->department_id) {
                    $user->employee->update(['department_id' => $deptId]);
                }
            }

            $this->users[$u['key']] = $user->fresh('employee');
        }
    }

    /* ================================================================
     * 2. Vendors
     * ================================================================ */
    private function seedVendors(): void
    {
        $vendors = [
            [
                'key' => 'v1',
                'data' => [
                    'name'                => 'DEMO Sahel Trading Ltd',
                    'contact_person'      => 'Bashir Aliyu',
                    'email'               => 'sales@demo-sahel.test',
                    'phone'               => '+234 805 555 1010',
                    'address'             => '14 Market Road, Maiduguri',
                    'city'                => 'Maiduguri',
                    'state'               => 'Borno',
                    'country'             => 'Nigeria',
                    'tax_id'              => 'TIN-DEMO-001',
                    'bank_name'           => 'First Bank of Nigeria',
                    'bank_account_number' => '3001112233',
                    'notes'               => 'Demo trading partner for office and field supplies.',
                    'status'              => 'active',
                ],
            ],
            [
                'key' => 'v2',
                'data' => [
                    'name'                => 'DEMO Northern Tech & Equipment',
                    'contact_person'      => 'Zara Musa',
                    'email'               => 'sales@demo-ntech.test',
                    'phone'               => '+234 803 555 2020',
                    'address'             => '8 Hospital Road, Yola',
                    'city'                => 'Yola',
                    'state'               => 'Adamawa',
                    'country'             => 'Nigeria',
                    'tax_id'              => 'TIN-DEMO-002',
                    'bank_name'           => 'Zenith Bank',
                    'bank_account_number' => '4002223344',
                    'notes'               => 'Demo IT, equipment and project supplies.',
                    'status'              => 'active',
                ],
            ],
        ];

        foreach ($vendors as $v) {
            $this->vendors[$v['key']] = Vendor::updateOrCreate(['name' => $v['data']['name']], $v['data']);
        }
    }

    /* ================================================================
     * 3. Projects (with team members + budget lines)
     * ================================================================ */
    private function seedProjects(): void
    {
        $progDeptId = Department::where('name', 'Programs')->value('id')
            ?? Department::where('code', 'PROGRAMS')->value('id');
        $procDeptId = Department::where('name', 'Procurement')->value('id')
            ?? Department::where('code', 'PROCUREMENT')->value('id');

        $manager = $this->users['procmgr'];

        $projects = [
            [
                'key' => 'p1',
                'data' => [
                    'project_code'       => 'DEMO-PRJ-001',
                    'name'               => 'DEMO Maiduguri Health Outreach 2026',
                    'description'        => 'Community health outreach across two LGAs in Borno State.',
                    'objectives'         => 'Reach 5,000 households with primary health screening and referral.',
                    'department_id'      => $progDeptId,
                    'project_manager_id' => $manager->id,
                    'start_date'         => '2026-04-01',
                    'end_date'           => '2026-12-31',
                    'location'           => 'Maiduguri, Borno',
                    'total_budget'       => 25000000,
                    'currency'           => 'NGN',
                    'status'             => 'active',
                    'notes'              => 'DEMO project — generated by demo:seed.',
                ],
                'team' => ['opsstaff', 'logstaff', 'finmgr'],
                'budget' => [
                    ['category' => 'Staff Costs',          'description' => 'Field officers and community mobilisers', 'unit' => 'months', 'quantity' => 9,  'unit_cost' => 800000],
                    ['category' => 'Equipment & Supplies', 'description' => 'Health screening kits and consumables',   'unit' => 'kits',   'quantity' => 50, 'unit_cost' => 25000],
                ],
            ],
            [
                'key' => 'p2',
                'data' => [
                    'project_code'       => 'DEMO-PRJ-002',
                    'name'               => 'DEMO Yola Education Program 2026',
                    'description'        => 'Out-of-school children re-enrolment and school supplies distribution.',
                    'objectives'         => 'Re-enrol 800 children and equip 12 schools with learning materials.',
                    'department_id'      => $progDeptId,
                    'project_manager_id' => $manager->id,
                    'start_date'         => '2026-05-01',
                    'end_date'           => '2027-04-30',
                    'location'           => 'Yola, Adamawa',
                    'total_budget'       => 18000000,
                    'currency'           => 'NGN',
                    'status'             => 'draft',
                    'notes'              => 'DEMO project — generated by demo:seed.',
                ],
                'team' => ['opsstaff', 'admin1'],
                'budget' => [
                    ['category' => 'Training & Capacity Building', 'description' => 'Teacher training workshops',     'unit' => 'workshops', 'quantity' => 6,   'unit_cost' => 350000],
                    ['category' => 'Equipment & Supplies',         'description' => 'School supplies distribution',   'unit' => 'kits',      'quantity' => 800, 'unit_cost' => 8500],
                ],
            ],
        ];

        foreach ($projects as $p) {
            $project = Project::updateOrCreate(
                ['project_code' => $p['data']['project_code']],
                $p['data']
            );
            $this->projects[$p['key']] = $project;

            foreach ($p['team'] as $userKey) {
                $employee = $this->users[$userKey]?->employee;
                if (!$employee) continue;
                ProjectTeamMember::updateOrCreate(
                    ['project_id' => $project->id, 'employee_id' => $employee->id],
                    [
                        'role'       => $userKey === 'finmgr' ? 'Finance Lead' : ($userKey === 'logstaff' ? 'Logistics Lead' : 'Project Officer'),
                        'start_date' => $project->start_date,
                    ]
                );
            }

            foreach ($p['budget'] as $b) {
                $cat = BudgetCategory::where('name', $b['category'])->first();
                if (!$cat) continue;
                ProjectBudgetLine::updateOrCreate(
                    ['project_id' => $project->id, 'budget_category_id' => $cat->id, 'description' => $b['description']],
                    [
                        'unit'       => $b['unit'],
                        'quantity'   => $b['quantity'],
                        'unit_cost'  => $b['unit_cost'],
                        'total_cost' => $b['quantity'] * $b['unit_cost'],
                    ]
                );
            }
        }
    }

    /* ================================================================
     * 4. BOQs — one approved (chains forward), one draft
     * ================================================================ */
    private function seedBoqs(): array
    {
        $approver = $this->users['finmgr'];
        $preparer = $this->users['opsstaff'];

        $boq1 = Boq::updateOrCreate(
            ['boq_number' => 'DEMO-BOQ-001'],
            [
                'title'             => 'DEMO Maiduguri health-kit BOQ',
                'project_code'      => $this->projects['p1']->project_code,
                'department'        => 'Programs',
                'category'          => 'Equipment & Supplies',
                'delivery_location' => 'Maiduguri Field Office',
                'prepared_by'       => $preparer->name,
                'status'            => 'approved',
                'date'              => '2026-04-05',
                'notes'             => 'DEMO BOQ feeding the Maiduguri outreach PR.',
                'signoffs'          => [[
                    'type'      => 'budget_holder',
                    'name'      => $approver->name,
                    'role'      => 'Finance Manager',
                    'email'     => $approver->email,
                    'date'      => '2026-04-08',
                    'signature' => $approver->name,
                ]],
            ]
        );
        BoqItem::updateOrCreate(
            ['boq_id' => $boq1->id, 'description' => 'Health screening kit (basic)'],
            ['section' => 'Field supplies', 'unit' => 'kit', 'quantity' => 50, 'unit_rate' => 25000, 'total' => 1250000]
        );
        BoqItem::updateOrCreate(
            ['boq_id' => $boq1->id, 'description' => 'Branded T-shirts for community mobilisers'],
            ['section' => 'Branding', 'unit' => 'pcs', 'quantity' => 30, 'unit_rate' => 4500, 'total' => 135000]
        );

        $boq2 = Boq::updateOrCreate(
            ['boq_number' => 'DEMO-BOQ-002'],
            [
                'title'             => 'DEMO Yola school supplies BOQ',
                'project_code'      => $this->projects['p2']->project_code,
                'department'        => 'Programs',
                'category'          => 'Equipment & Supplies',
                'delivery_location' => 'Yola Field Office',
                'prepared_by'       => $preparer->name,
                'status'            => 'draft',
                'date'              => '2026-05-02',
                'notes'             => 'DEMO BOQ in draft state for review.',
            ]
        );
        BoqItem::updateOrCreate(
            ['boq_id' => $boq2->id, 'description' => 'School supplies kit (per pupil)'],
            ['section' => 'Distribution', 'unit' => 'kit', 'quantity' => 800, 'unit_rate' => 8500, 'total' => 6800000]
        );

        return ['b1' => $boq1, 'b2' => $boq2];
    }

    /* ================================================================
     * 5. Purchase Requests — one approved (chains forward), one pending
     * ================================================================ */
    private function seedPRs(array $boqs): array
    {
        $progDeptId = Department::where('name', 'Programs')->value('id');
        $requester = $this->users['opsstaff']->employee;
        $approver  = $this->users['finmgr'];

        if (!$requester) {
            $this->command?->warn('No employee for ops staff — skipping PRs.');
            return [];
        }

        $pr1 = Requisition::updateOrCreate(
            ['requisition_number' => 'DEMO-PR-001'],
            [
                'department_id'   => $progDeptId,
                'requested_by'    => $requester->id,
                'submitted_by'    => $this->users['opsstaff']->id,
                'project_id'      => $this->projects['p1']->id,
                'budget_holder_id'=> $approver->id,
                'title'           => 'DEMO Maiduguri health-kit PR',
                'date'            => '2026-04-10',
                'justification'   => 'Health screening rollout starts 2026-04-22; kits must be on-site one week prior.',
                'priority'        => 'high',
                'needed_by'       => '2026-04-18',
                'estimated_cost'  => 1385000,
                'notes'           => 'Driven by approved DEMO-BOQ-001.',
                'boq'             => $boqs['b1']->boq_number,
                'project_code'    => $this->projects['p1']->project_code,
                'currency'        => 'NGN',
                'status'          => 'approved',
                'signoffs'        => [[
                    'type'      => 'budget_holder',
                    'name'      => $approver->name,
                    'role'      => 'Finance Manager',
                    'email'     => $approver->email,
                    'date'      => '2026-04-12',
                    'signature' => $approver->name,
                ]],
            ]
        );
        RequisitionItem::updateOrCreate(
            ['requisition_id' => $pr1->id, 'description' => 'Health screening kit (basic)'],
            ['quantity' => 50, 'unit' => 'kit', 'estimated_unit_cost' => 25000, 'estimated_total_cost' => 1250000, 'project_code' => $this->projects['p1']->project_code]
        );
        RequisitionItem::updateOrCreate(
            ['requisition_id' => $pr1->id, 'description' => 'Branded T-shirts for community mobilisers'],
            ['quantity' => 30, 'unit' => 'pcs', 'estimated_unit_cost' => 4500, 'estimated_total_cost' => 135000, 'project_code' => $this->projects['p1']->project_code]
        );
        $this->ensureApprovalSteps('requisition', $pr1->id, $approver->id, /* allApproved */ true);

        $pr2 = Requisition::updateOrCreate(
            ['requisition_number' => 'DEMO-PR-002'],
            [
                'department_id'  => $progDeptId,
                'requested_by'   => $requester->id,
                'submitted_by'   => $this->users['opsstaff']->id,
                'project_id'     => $this->projects['p2']->id,
                'title'          => 'DEMO Yola school supplies PR',
                'date'           => '2026-05-05',
                'justification'  => 'Distribution scheduled for term opening; pending budget-holder review.',
                'priority'       => 'medium',
                'needed_by'      => '2026-06-10',
                'estimated_cost' => 6800000,
                'project_code'   => $this->projects['p2']->project_code,
                'currency'       => 'NGN',
                'status'         => 'submitted',
            ]
        );
        RequisitionItem::updateOrCreate(
            ['requisition_id' => $pr2->id, 'description' => 'School supplies kit (per pupil)'],
            ['quantity' => 800, 'unit' => 'kit', 'estimated_unit_cost' => 8500, 'estimated_total_cost' => 6800000, 'project_code' => $this->projects['p2']->project_code]
        );
        $this->ensureApprovalSteps('requisition', $pr2->id, $approver->id, /* allApproved */ false);

        return ['pr1' => $pr1, 'pr2' => $pr2];
    }

    /* ================================================================
     * 6. RFQs — one targeted multi-vendor (chains), one open call
     * ================================================================ */
    private function seedRfqs(array $prs): array
    {
        if (empty($prs)) return [];
        $procmgr = $this->users['procmgr'];
        $logstaff = $this->users['logstaff'];

        $rfq1 = Rfq::updateOrCreate(
            ['rfq_number' => 'DEMO-RFQ-001'],
            [
                'created_by'      => $procmgr->id,
                'title'           => 'DEMO RFQ — Maiduguri health-kit (multi-vendor)',
                'pr_reference'    => $prs['pr1']->requisition_number,
                'project_code'    => $this->projects['p1']->project_code,
                'currency'        => 'NGN',
                'request_types'   => ['Goods'],
                'vendor_id'       => $this->vendors['v1']->id, // primary recipient (legacy)
                'status'          => 'closed',
                'scope'           => 'targeted',
                'issue_date'      => '2026-04-15',
                'deadline'        => '2026-04-22',
                'delivery_address'=> 'Maiduguri Field Office',
                'delivery_terms'  => '7 days from PO',
                'notes'           => 'DEMO targeted RFQ — both vendors invited.',
                'signoffs'        => [[
                    'type'      => 'Logistics Officer',
                    'name'      => $logstaff->name,
                    'date'      => '2026-04-22',
                    'signature' => $logstaff->name,
                ]],
            ]
        );
        $rfq1->vendors()->sync([$this->vendors['v1']->id, $this->vendors['v2']->id]);
        RfqItem::updateOrCreate(
            ['rfq_id' => $rfq1->id, 'description' => 'Health screening kit (basic)'],
            ['item_number' => 'HK-01', 'unit' => 'kit', 'quantity' => 50, 'unit_cost' => 25000, 'total' => 1250000]
        );
        RfqItem::updateOrCreate(
            ['rfq_id' => $rfq1->id, 'description' => 'Branded T-shirts for community mobilisers'],
            ['item_number' => 'HK-02', 'unit' => 'pcs', 'quantity' => 30, 'unit_cost' => 4500, 'total' => 135000]
        );

        $rfq2 = Rfq::updateOrCreate(
            ['rfq_number' => 'DEMO-RFQ-002'],
            [
                'created_by'      => $procmgr->id,
                'title'           => 'DEMO RFQ — Yola school supplies (open call)',
                'pr_reference'    => $prs['pr2']->requisition_number,
                'project_code'    => $this->projects['p2']->project_code,
                'currency'        => 'NGN',
                'request_types'   => ['Goods'],
                'vendor_id'       => null,
                'status'          => 'open',
                'scope'           => 'open',
                'advertised_on'   => 'Posted on casi360.com/tenders and on the office notice board, May 2026.',
                'issue_date'      => '2026-05-12',
                'deadline'        => '2026-05-26',
                'delivery_address'=> 'Yola Field Office',
                'delivery_terms'  => '14 days from PO',
                'notes'           => 'DEMO open-call RFQ — any qualified vendor may respond.',
            ]
        );
        $rfq2->vendors()->sync([]); // open call — no pinned vendors
        RfqItem::updateOrCreate(
            ['rfq_id' => $rfq2->id, 'description' => 'School supplies kit (per pupil)'],
            ['item_number' => 'SK-01', 'unit' => 'kit', 'quantity' => 800, 'unit_cost' => 8500, 'total' => 6800000]
        );

        return ['rfq1' => $rfq1, 'rfq2' => $rfq2];
    }

    /* ================================================================
     * 7. POs — one approved (chains forward), one draft
     * ================================================================ */
    private function seedPOs(array $prs, array $rfqs): array
    {
        if (empty($prs)) return [];
        $procDeptId = Department::where('name', 'Procurement')->value('id');
        $requester  = $this->users['opsstaff']->employee;
        $procmgr    = $this->users['procmgr'];
        $finmgr     = $this->users['finmgr'];

        $po1 = PurchaseOrder::updateOrCreate(
            ['po_number' => 'DEMO-PO-001'],
            [
                'vendor_id'              => $this->vendors['v1']->id,
                'department_id'          => $procDeptId,
                'requested_by'           => $requester?->id,
                'submitted_by'           => $procmgr->id,
                'order_date'             => '2026-04-25',
                'expected_delivery_date' => '2026-05-02',
                'subtotal'               => 1385000,
                'tax_amount'             => 0,
                'discount_amount'        => 0,
                'total_amount'           => 1385000,
                'currency'               => 'NGN',
                'notes'                  => 'DEMO PO awarded from RFQ-001 to Sahel Trading.',
                'pr_reference'           => $prs['pr1']->requisition_number,
                'rfq_reference'          => $rfqs['rfq1']?->rfq_number,
                'deliver_name'           => 'Maiduguri Field Office',
                'deliver_address'        => 'Maiduguri Field Office, Borno',
                'deliver_position'       => 'Field Logistics',
                'deliver_contact'        => $this->users['logstaff']->phone,
                'payment_terms'          => 'Net 30',
                'delivery_terms'         => '7 days from PO',
                'status'                 => 'approved',
                'payment_status'         => 'unpaid',
                'signoffs'               => [
                    ['type' => 'procurement_manager', 'name' => $procmgr->name, 'date' => '2026-04-25', 'signature' => $procmgr->name],
                    ['type' => 'finance_manager',     'name' => $finmgr->name,  'date' => '2026-04-26', 'signature' => $finmgr->name],
                ],
            ]
        );
        PurchaseOrderItem::updateOrCreate(
            ['purchase_order_id' => $po1->id, 'description' => 'Health screening kit (basic)'],
            ['quantity' => 50, 'received_quantity' => 50, 'unit' => 'kit', 'unit_price' => 25000, 'total_price' => 1250000, 'project_code' => $this->projects['p1']->project_code]
        );
        PurchaseOrderItem::updateOrCreate(
            ['purchase_order_id' => $po1->id, 'description' => 'Branded T-shirts for community mobilisers'],
            ['quantity' => 30, 'received_quantity' => 30, 'unit' => 'pcs', 'unit_price' => 4500, 'total_price' => 135000, 'project_code' => $this->projects['p1']->project_code]
        );
        $this->ensureApprovalSteps('purchase_order', $po1->id, $finmgr->id, /* allApproved */ true);

        $po2 = PurchaseOrder::updateOrCreate(
            ['po_number' => 'DEMO-PO-002'],
            [
                'vendor_id'              => $this->vendors['v2']->id,
                'department_id'          => $procDeptId,
                'requested_by'           => $requester?->id,
                'submitted_by'           => $procmgr->id,
                'order_date'             => '2026-05-15',
                'expected_delivery_date' => '2026-05-30',
                'subtotal'               => 6800000,
                'tax_amount'             => 0,
                'discount_amount'        => 0,
                'total_amount'           => 6800000,
                'currency'               => 'NGN',
                'notes'                  => 'DEMO PO in draft awaiting RFQ award.',
                'pr_reference'           => $prs['pr2']->requisition_number,
                'deliver_name'           => 'Yola Field Office',
                'deliver_address'        => 'Yola Field Office, Adamawa',
                'payment_terms'          => 'Net 30',
                'delivery_terms'         => '14 days from PO',
                'status'                 => 'draft',
                'payment_status'         => 'unpaid',
            ]
        );
        PurchaseOrderItem::updateOrCreate(
            ['purchase_order_id' => $po2->id, 'description' => 'School supplies kit (per pupil)'],
            ['quantity' => 800, 'received_quantity' => 0, 'unit' => 'kit', 'unit_price' => 8500, 'total_price' => 6800000, 'project_code' => $this->projects['p2']->project_code]
        );

        return ['po1' => $po1, 'po2' => $po2];
    }

    /* ================================================================
     * 8. GRNs — one received (chains forward), one partial
     * ================================================================ */
    private function seedGrns(array $pos): array
    {
        if (empty($pos)) return [];
        $logstaff = $this->users['logstaff'];

        $grn1 = Grn::updateOrCreate(
            ['grn_number' => 'DEMO-GRN-001'],
            [
                'created_by'        => $logstaff->id,
                'po_reference'      => $pos['po1']->po_number,
                'vendor_id'         => $this->vendors['v1']->id,
                'office'            => 'Maiduguri Field Office',
                'received_by'       => $logstaff->name,
                'status'            => 'accepted',
                'received_date'     => '2026-05-02',
                'delivery_note_no'  => 'DN-DEMO-001',
                'notes'             => 'DEMO GRN — full receipt of health-kit PO.',
                'signoffs'          => [[
                    'type' => 'logistics_officer', 'name' => $logstaff->name, 'date' => '2026-05-02', 'signature' => $logstaff->name,
                ]],
            ]
        );
        GrnItem::updateOrCreate(
            ['grn_id' => $grn1->id, 'description' => 'Health screening kit (basic)'],
            ['ordered_qty' => 50, 'received_qty' => 50, 'quality_status' => 'good', 'accepted_qty' => 50, 'rejected_qty' => 0]
        );
        GrnItem::updateOrCreate(
            ['grn_id' => $grn1->id, 'description' => 'Branded T-shirts for community mobilisers'],
            ['ordered_qty' => 30, 'received_qty' => 30, 'quality_status' => 'good', 'accepted_qty' => 30, 'rejected_qty' => 0]
        );

        $grn2 = Grn::updateOrCreate(
            ['grn_number' => 'DEMO-GRN-002'],
            [
                'created_by'   => $logstaff->id,
                'po_reference' => $pos['po2']->po_number,
                'vendor_id'    => $this->vendors['v2']->id,
                'office'       => 'Yola Field Office',
                'received_by'  => $logstaff->name,
                'status'       => 'partial',
                'received_date'=> '2026-06-01',
                'delivery_note_no' => 'DN-DEMO-002',
                'notes'        => 'DEMO GRN — partial receipt pending second batch.',
            ]
        );
        GrnItem::updateOrCreate(
            ['grn_id' => $grn2->id, 'description' => 'School supplies kit (per pupil)'],
            ['ordered_qty' => 800, 'received_qty' => 400, 'quality_status' => 'good', 'accepted_qty' => 400, 'rejected_qty' => 0]
        );

        return ['grn1' => $grn1, 'grn2' => $grn2];
    }

    /* ================================================================
     * 9. Invoices — one approved (chains forward), one pending
     * ================================================================ */
    private function seedInvoices(array $pos): array
    {
        if (empty($pos)) return [];
        $procmgr = $this->users['procmgr'];
        $finmgr  = $this->users['finmgr'];

        $inv1 = Invoice::updateOrCreate(
            ['invoice_number' => 'DEMO-INV-001'],
            [
                'po_id'        => $pos['po1']->id,
                'vendor_id'    => $this->vendors['v1']->id,
                'amount'       => 1385000,
                'currency'     => 'NGN',
                'invoice_date' => '2026-05-03',
                'due_date'     => '2026-06-02',
                'notes'        => 'DEMO invoice — full delivery of PO-001.',
                'status'       => 'approved',
                'created_by'   => $procmgr->id,
                'submitted_by' => $procmgr->id,
                'approved_by'  => $finmgr->id,
                'approved_at'  => '2026-05-04 10:00:00',
            ]
        );

        $inv2 = Invoice::updateOrCreate(
            ['invoice_number' => 'DEMO-INV-002'],
            [
                'po_id'        => $pos['po2']->id,
                'vendor_id'    => $this->vendors['v2']->id,
                'amount'       => 3400000,
                'currency'     => 'NGN',
                'invoice_date' => '2026-06-02',
                'due_date'     => '2026-07-02',
                'notes'        => 'DEMO invoice — partial billing for first batch.',
                'status'       => 'pending',
                'created_by'   => $procmgr->id,
                'submitted_by' => $procmgr->id,
            ]
        );

        return ['inv1' => $inv1, 'inv2' => $inv2];
    }

    /* ================================================================
     * 10. RFPs — one disbursed, one pending
     * ================================================================ */
    private function seedRfps(array $invoices): void
    {
        if (empty($invoices)) return;
        $finmgr  = $this->users['finmgr'];
        $procmgr = $this->users['procmgr'];

        $rfp1 = Rfp::updateOrCreate(
            ['rfp_number' => 'DEMO-RFP-001'],
            [
                'invoice_id'    => $invoices['inv1']->id,
                'po_reference'  => 'DEMO-PO-001',
                'grn_reference' => 'DEMO-GRN-001',
                'project_code'  => $this->projects['p1']->project_code,
                'vendor_id'     => $this->vendors['v1']->id,
                'payee'         => $this->vendors['v1']->name,
                'currency'      => 'NGN',
                'department'    => 'Programs',
                'budget_line'   => 'Equipment & Supplies',
                'date'          => '2026-05-05',
                'status'        => 'paid',
                'payment_date'  => '2026-05-08',
                'subtotal'      => 1385000,
                'tax_amount'    => 0,
                'tax_rate'      => 0,
                'total_amount'  => 1385000,
                'payment_method'=> 'bank_transfer',
                'bank_details'  => $this->vendors['v1']->bank_name . ' — ' . $this->vendors['v1']->bank_account_number,
                'notes'         => 'DEMO RFP — disbursed against approved invoice.',
                'signoffs'      => [
                    ['type' => 'finance_manager', 'name' => $finmgr->name,  'date' => '2026-05-06', 'signature' => $finmgr->name],
                    ['type' => 'procurement',     'name' => $procmgr->name, 'date' => '2026-05-06', 'signature' => $procmgr->name],
                ],
            ]
        );
        RfpItem::updateOrCreate(
            ['rfp_id' => $rfp1->id, 'description' => 'Maiduguri health-kit settlement'],
            ['project_code' => $this->projects['p1']->project_code, 'budget_line' => 'Equipment & Supplies', 'quantity' => 1, 'unit_cost' => 1385000, 'dept' => 'Programs', 'total' => 1385000]
        );

        $rfp2 = Rfp::updateOrCreate(
            ['rfp_number' => 'DEMO-RFP-002'],
            [
                'invoice_id'   => $invoices['inv2']->id,
                'po_reference' => 'DEMO-PO-002',
                'grn_reference'=> 'DEMO-GRN-002',
                'project_code' => $this->projects['p2']->project_code,
                'vendor_id'    => $this->vendors['v2']->id,
                'payee'        => $this->vendors['v2']->name,
                'currency'     => 'NGN',
                'department'   => 'Programs',
                'budget_line'  => 'Equipment & Supplies',
                'date'         => '2026-06-03',
                'status'       => 'submitted',
                'subtotal'     => 3400000,
                'tax_amount'   => 0,
                'tax_rate'     => 0,
                'total_amount' => 3400000,
                'payment_method'=> 'bank_transfer',
                'bank_details' => $this->vendors['v2']->bank_name . ' — ' . $this->vendors['v2']->bank_account_number,
                'notes'        => 'DEMO RFP — pending finance approval.',
            ]
        );
        RfpItem::updateOrCreate(
            ['rfp_id' => $rfp2->id, 'description' => 'Yola school supplies — first batch settlement'],
            ['project_code' => $this->projects['p2']->project_code, 'budget_line' => 'Equipment & Supplies', 'quantity' => 1, 'unit_cost' => 3400000, 'dept' => 'Programs', 'total' => 3400000]
        );
    }

    /* ================================================================
     * Helpers
     * ================================================================ */

    /**
     * Add a minimal 2-step approval trail to an approvable. When
     * $allApproved is true both steps are marked approved (so the
     * record looks fully signed off); otherwise both are pending.
     * Idempotent — re-running just no-ops via updateOrCreate.
     */
    private function ensureApprovalSteps(string $type, string $approvableId, ?string $actorId, bool $allApproved): void
    {
        $steps = [
            ['order' => 1, 'type' => 'manager_review', 'label' => 'Manager Review'],
            ['order' => 2, 'type' => 'finance_check', 'label' => 'Finance Verification'],
        ];

        foreach ($steps as $i => $s) {
            ApprovalStep::updateOrCreate(
                ['approvable_type' => $type, 'approvable_id' => $approvableId, 'step_order' => $s['order']],
                [
                    'step_type'  => $s['type'],
                    'step_label' => $s['label'],
                    'status'     => $allApproved ? 'approved' : 'pending',
                    'acted_by'   => $allApproved ? $actorId : null,
                    'acted_at'   => $allApproved ? now()->subDays(2 - $i) : null,
                ]
            );
        }
    }
}
