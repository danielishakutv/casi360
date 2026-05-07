<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use Illuminate\Database\Seeder;

/**
 * Opt-in demo employees for development/testing — twenty-five named
 * people spread across the seeded departments and designations.
 *
 * NOT called by DatabaseSeeder. Run explicitly when you want demo
 * fixtures:
 *
 *     php artisan db:seed --class=DemoEmployeesSeeder
 *
 * Production deployments and post-reset databases should NEVER pull
 * this in — keeps the system clean of fake people. Depends on
 * HRSeeder having run first so the departments and designations exist.
 */
class DemoEmployeesSeeder extends Seeder
{
    public function run(): void
    {
        $deptIdByName = Department::query()->pluck('id', 'name')->toArray();

        if (empty($deptIdByName)) {
            $this->command?->warn('No departments found — run HRSeeder first.');
            return;
        }

        $findDesignationId = function (string $title, string $deptName) use ($deptIdByName): ?string {
            $deptId = $deptIdByName[$deptName] ?? null;
            if (!$deptId) {
                return null;
            }
            return Designation::where('title', $title)
                ->where('department_id', $deptId)
                ->value('id');
        };

        $employees = [
            ['name' => 'Adaeze Obi',         'email' => 'adaeze@casi.org',    'phone' => '+234 801 111 0001', 'department' => 'Administration',     'designation' => 'Admin Officer',          'status' => 'active',     'join_date' => '2023-01-15', 'salary' => 450000, 'manager' => 'Daniel Okonkwo'],
            ['name' => 'Bayo Adewale',       'email' => 'bayo@casi.org',      'phone' => '+234 801 111 0002', 'department' => 'Programs',           'designation' => 'Program Manager',        'status' => 'active',     'join_date' => '2023-02-20', 'salary' => 380000, 'manager' => 'Samuel Eze'],
            ['name' => 'Chidinma Eze',       'email' => 'chidinma@casi.org',  'phone' => '+234 801 111 0003', 'department' => 'Finance',            'designation' => 'Accountant',             'status' => 'active',     'join_date' => '2023-03-10', 'salary' => 420000, 'manager' => 'Helen Ogundimu'],
            ['name' => 'David Okafor',       'email' => 'david@casi.org',     'phone' => '+234 801 111 0004', 'department' => 'IT',                 'designation' => 'Software Developer',     'status' => 'active',     'join_date' => '2023-04-05', 'salary' => 550000, 'manager' => 'Ibrahim Hassan'],
            ['name' => 'Emeka Nwankwo',      'email' => 'emeka@casi.org',     'phone' => '+234 801 111 0005', 'department' => 'Human Resources',    'designation' => 'HR Officer',             'status' => 'active',     'join_date' => '2023-05-12', 'salary' => 350000, 'manager' => 'Amina Bello'],
            ['name' => 'Fatima Ibrahim',     'email' => 'fatima@casi.org',    'phone' => '+234 801 111 0006', 'department' => 'Programs',           'designation' => 'Project Assistant',      'status' => 'active',     'join_date' => '2023-06-18', 'salary' => 320000, 'manager' => 'Samuel Eze'],
            ['name' => 'George Adekunle',    'email' => 'george@casi.org',    'phone' => '+234 801 111 0007', 'department' => 'Logistics',          'designation' => 'Logistics Officer',      'status' => 'on_leave',   'join_date' => '2023-07-22', 'salary' => 380000, 'manager' => 'Patricia Mba'],
            ['name' => 'Helen Ogundimu',     'email' => 'helen@casi.org',     'phone' => '+234 801 111 0008', 'department' => 'Finance',            'designation' => 'Finance Manager',        'status' => 'active',     'join_date' => '2023-08-30', 'salary' => 520000, 'manager' => 'Daniel Okonkwo'],
            ['name' => 'Ibrahim Hassan',     'email' => 'ibrahim@casi.org',   'phone' => '+234 801 111 0009', 'department' => 'IT',                 'designation' => 'IT Officer',             'status' => 'active',     'join_date' => '2023-09-14', 'salary' => 480000, 'manager' => 'Daniel Okonkwo'],
            ['name' => 'Joy Adebayo',        'email' => 'joy@casi.org',       'phone' => '+234 801 111 0010', 'department' => 'Programs',           'designation' => 'Project Assistant',      'status' => 'active',     'join_date' => '2023-10-05', 'salary' => 370000, 'manager' => 'Samuel Eze'],
            ['name' => 'Kalu Chukwuma',      'email' => 'kalu@casi.org',      'phone' => '+234 801 111 0011', 'department' => 'Administration',     'designation' => 'Admin Officer',          'status' => 'active',     'join_date' => '2023-11-20', 'salary' => 280000, 'manager' => 'Adaeze Obi'],
            ['name' => 'Linda Ogbonna',      'email' => 'linda@casi.org',     'phone' => '+234 801 111 0012', 'department' => 'Communications',     'designation' => 'Communications Officer', 'status' => 'active',     'join_date' => '2024-01-08', 'salary' => 390000, 'manager' => 'Daniel Okonkwo'],
            ['name' => 'Mohammed Yusuf',     'email' => 'mohammed@casi.org',  'phone' => '+234 801 111 0013', 'department' => 'Programs',           'designation' => 'Project Assistant',      'status' => 'active',     'join_date' => '2024-02-14', 'salary' => 340000, 'manager' => 'Samuel Eze'],
            ['name' => 'Nneka Uche',         'email' => 'nneka@casi.org',     'phone' => '+234 801 111 0014', 'department' => 'Human Resources',    'designation' => 'Recruitment Officer',    'status' => 'active',     'join_date' => '2024-03-01', 'salary' => 410000, 'manager' => 'Amina Bello'],
            ['name' => 'Oluwaseun Bakare',   'email' => 'seun@casi.org',      'phone' => '+234 801 111 0015', 'department' => 'Finance',            'designation' => 'Finance Officer',        'status' => 'active',     'join_date' => '2024-03-15', 'salary' => 400000, 'manager' => 'Helen Ogundimu'],
            ['name' => 'Patricia Mba',       'email' => 'patricia@casi.org',  'phone' => '+234 801 111 0016', 'department' => 'Operations',         'designation' => 'Operations Manager',     'status' => 'active',     'join_date' => '2024-04-01', 'salary' => 500000, 'manager' => 'Daniel Okonkwo'],
            ['name' => 'Rasheed Abdullahi',  'email' => 'rasheed@casi.org',   'phone' => '+234 801 111 0017', 'department' => 'IT',                 'designation' => 'IT Officer',             'status' => 'active',     'join_date' => '2024-04-20', 'salary' => 310000, 'manager' => 'Ibrahim Hassan'],
            ['name' => 'Sandra Okoro',       'email' => 'sandra@casi.org',    'phone' => '+234 801 111 0018', 'department' => 'Programs',           'designation' => 'Program Manager',        'status' => 'active',     'join_date' => '2024-05-10', 'salary' => 480000, 'manager' => 'Samuel Eze'],
            ['name' => 'Tunde Afolabi',      'email' => 'tunde@casi.org',     'phone' => '+234 801 111 0019', 'department' => 'Administration',     'designation' => 'Executive Director',     'status' => 'active',     'join_date' => '2024-06-01', 'salary' => 360000, 'manager' => 'Daniel Okonkwo'],
            ['name' => 'Ugochi Onyekachi',   'email' => 'ugochi@casi.org',    'phone' => '+234 801 111 0020', 'department' => 'Communications',     'designation' => 'Communications Officer', 'status' => 'on_leave',   'join_date' => '2024-06-15', 'salary' => 370000, 'manager' => 'Linda Ogbonna'],
            ['name' => 'Victor Emenike',     'email' => 'victor@casi.org',    'phone' => '+234 801 111 0021', 'department' => 'Finance',            'designation' => 'Finance Officer',        'status' => 'active',     'join_date' => '2024-07-01', 'salary' => 350000, 'manager' => 'Helen Ogundimu'],
            ['name' => 'Winifred Akinola',   'email' => 'winifred@casi.org',  'phone' => '+234 801 111 0022', 'department' => 'Human Resources',    'designation' => 'HR Officer',             'status' => 'active',     'join_date' => '2024-07-20', 'salary' => 380000, 'manager' => 'Amina Bello'],
            ['name' => 'Xavier Osei',        'email' => 'xavier@casi.org',    'phone' => '+234 801 111 0023', 'department' => 'Programs',           'designation' => 'Project Assistant',      'status' => 'terminated', 'join_date' => '2024-08-05', 'salary' => 360000, 'manager' => 'Samuel Eze'],
            ['name' => 'Yetunde Balogun',    'email' => 'yetunde@casi.org',   'phone' => '+234 801 111 0024', 'department' => 'Logistics',          'designation' => 'Logistics Officer',      'status' => 'active',     'join_date' => '2024-08-20', 'salary' => 390000, 'manager' => 'Patricia Mba'],
            ['name' => 'Zainab Abubakar',    'email' => 'zainab@casi.org',    'phone' => '+234 801 111 0025', 'department' => 'IT',                 'designation' => 'Data Analyst',           'status' => 'active',     'join_date' => '2024-09-01', 'salary' => 430000, 'manager' => 'Ibrahim Hassan'],
        ];

        $staffNum = 1001;
        foreach ($employees as $emp) {
            $deptName = $emp['department'];
            $desigTitle = $emp['designation'];
            unset($emp['department'], $emp['designation']);

            $emp['department_id']  = $deptIdByName[$deptName] ?? null;
            $emp['designation_id'] = $findDesignationId($desigTitle, $deptName);

            if (!$emp['department_id']) {
                $this->command?->warn("Skipping {$emp['name']} — department '{$deptName}' not found.");
                continue;
            }

            $emp['staff_id'] = 'CASI-' . str_pad($staffNum++, 4, '0', STR_PAD_LEFT);
            if ($emp['status'] === 'terminated') {
                $emp['termination_date'] = '2025-01-15';
            }

            Employee::updateOrCreate(['email' => $emp['email']], $emp);
        }

        $this->command?->info('Demo employees seeded: ' . count($employees) . ' people.');
    }
}
