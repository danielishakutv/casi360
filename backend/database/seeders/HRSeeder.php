<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use Illuminate\Database\Seeder;

class HRSeeder extends Seeder
{
    public function run(): void
    {
        // ---- Departments ----
        $departments = [
            ['name' => 'Administration', 'head' => 'Daniel Okonkwo', 'description' => 'Oversees organizational administration and governance', 'color' => '#6366F1'],
            ['name' => 'Programs', 'head' => 'Samuel Eze', 'description' => 'Manages community programs and interventions', 'color' => '#8B5CF6'],
            ['name' => 'Finance', 'head' => 'Helen Ogundimu', 'description' => 'Handles budgeting, accounting, and financial reporting', 'color' => '#EC4899'],
            ['name' => 'IT', 'head' => 'Ibrahim Hassan', 'description' => 'Manages technology infrastructure and digital solutions', 'color' => '#14B8A6'],
            ['name' => 'HR', 'head' => 'Amina Bello', 'description' => 'Human resources management and staff welfare', 'color' => '#F97316'],
            ['name' => 'Operations', 'head' => 'Patricia Mba', 'description' => 'Logistics, procurement, and operational coordination', 'color' => '#06B6D4'],
            ['name' => 'Communications', 'head' => 'Linda Ogbonna', 'description' => 'Public relations, media, and external communications', 'color' => '#EAB308'],
            ['name' => 'Legal', 'head' => null, 'description' => 'Legal compliance and advisory services', 'color' => '#64748B'],
            ['name' => 'Research', 'head' => null, 'description' => 'Research, monitoring, and evaluation', 'color' => '#A855F7'],
            ['name' => 'Partnerships', 'head' => null, 'description' => 'Donor relations and strategic partnerships', 'color' => '#22C55E'],
        ];

        $deptMap = [];
        foreach ($departments as $dept) {
            $d = Department::updateOrCreate(
                ['name' => $dept['name']],
                $dept
            );
            $deptMap[$dept['name']] = $d->id;
        }

        // ---- Designations ----
        $designations = [
            ['title' => 'Executive Director', 'department' => 'Administration', 'level' => 'executive', 'description' => 'Overall leadership and strategic direction of the organization'],
            ['title' => 'Deputy Director', 'department' => 'Administration', 'level' => 'executive', 'description' => 'Supports the Executive Director in managing organizational operations'],
            ['title' => 'Program Manager', 'department' => 'Programs', 'level' => 'senior', 'description' => 'Oversees program design, implementation, and evaluation'],
            ['title' => 'HR Manager', 'department' => 'HR', 'level' => 'senior', 'description' => 'Manages recruitment, employee relations, and HR policies'],
            ['title' => 'Finance Officer', 'department' => 'Finance', 'level' => 'mid', 'description' => 'Handles financial reporting, budgeting, and compliance'],
            ['title' => 'IT Administrator', 'department' => 'IT', 'level' => 'mid', 'description' => 'Maintains IT infrastructure and supports digital operations'],
            ['title' => 'Field Coordinator', 'department' => 'Operations', 'level' => 'mid', 'description' => 'Coordinates field activities and community engagement programs'],
            ['title' => 'Communications Officer', 'department' => 'Communications', 'level' => 'mid', 'description' => 'Manages public relations, media, and organizational communications'],
            ['title' => 'M&E Officer', 'department' => 'Research', 'level' => 'mid', 'description' => 'Monitors program outcomes and conducts impact evaluations'],
            ['title' => 'Legal Advisor', 'department' => 'Legal', 'level' => 'senior', 'description' => 'Provides legal counsel and ensures regulatory compliance'],
            ['title' => 'Logistics Officer', 'department' => 'Operations', 'level' => 'mid', 'description' => 'Manages procurement, supply chain, and asset management'],
            ['title' => 'Project Assistant', 'department' => 'Programs', 'level' => 'junior', 'description' => 'Supports project teams with administrative and field tasks'],
            ['title' => 'Data Analyst', 'department' => 'IT', 'level' => 'mid', 'description' => 'Analyzes organizational data to drive informed decision-making'],
            ['title' => 'Accountant', 'department' => 'Finance', 'level' => 'mid', 'description' => 'Manages accounts payable, receivable, and financial records'],
            ['title' => 'Administrative Assistant', 'department' => 'Administration', 'level' => 'junior', 'description' => 'Provides clerical and administrative support to departments'],
            ['title' => 'Software Developer', 'department' => 'IT', 'level' => 'mid', 'description' => 'Develops and maintains software applications'],
            ['title' => 'HR Officer', 'department' => 'HR', 'level' => 'mid', 'description' => 'Supports HR operations including recruitment and employee relations'],
            ['title' => 'Recruitment Lead', 'department' => 'HR', 'level' => 'mid', 'description' => 'Leads talent acquisition and recruitment processes'],
            ['title' => 'Finance Manager', 'department' => 'Finance', 'level' => 'senior', 'description' => 'Oversees financial operations and reporting'],
            ['title' => 'Operations Manager', 'department' => 'Operations', 'level' => 'senior', 'description' => 'Manages day-to-day operational activities'],
        ];

        $desigMap = [];
        foreach ($designations as $desig) {
            $deptName = $desig['department'];
            unset($desig['department']);
            $desig['department_id'] = $deptMap[$deptName];
            $d = Designation::updateOrCreate(
                ['title' => $desig['title'], 'department_id' => $desig['department_id']],
                $desig
            );
            $desigMap[$d->title . '|' . $deptName] = $d->id;
        }

        // Helper to find designation id
        $findDesig = function (string $title, string $dept) use ($desigMap) {
            return $desigMap[$title . '|' . $dept] ?? null;
        };

        // ---- Employees ----
        $employees = [
            ['name' => 'Adaeze Obi', 'email' => 'adaeze@casi.org', 'phone' => '+234 801 111 0001', 'department' => 'Administration', 'designation' => 'Administrative Assistant', 'status' => 'active', 'join_date' => '2023-01-15', 'salary' => 450000, 'manager' => 'Daniel Okonkwo'],
            ['name' => 'Bayo Adewale', 'email' => 'bayo@casi.org', 'phone' => '+234 801 111 0002', 'department' => 'Programs', 'designation' => 'Program Manager', 'status' => 'active', 'join_date' => '2023-02-20', 'salary' => 380000, 'manager' => 'Samuel Eze'],
            ['name' => 'Chidinma Eze', 'email' => 'chidinma@casi.org', 'phone' => '+234 801 111 0003', 'department' => 'Finance', 'designation' => 'Accountant', 'status' => 'active', 'join_date' => '2023-03-10', 'salary' => 420000, 'manager' => 'Helen Ogundimu'],
            ['name' => 'David Okafor', 'email' => 'david@casi.org', 'phone' => '+234 801 111 0004', 'department' => 'IT', 'designation' => 'Software Developer', 'status' => 'active', 'join_date' => '2023-04-05', 'salary' => 550000, 'manager' => 'Ibrahim Hassan'],
            ['name' => 'Emeka Nwankwo', 'email' => 'emeka@casi.org', 'phone' => '+234 801 111 0005', 'department' => 'HR', 'designation' => 'HR Officer', 'status' => 'active', 'join_date' => '2023-05-12', 'salary' => 350000, 'manager' => 'Amina Bello'],
            ['name' => 'Fatima Ibrahim', 'email' => 'fatima@casi.org', 'phone' => '+234 801 111 0006', 'department' => 'Programs', 'designation' => 'Project Assistant', 'status' => 'active', 'join_date' => '2023-06-18', 'salary' => 320000, 'manager' => 'Samuel Eze'],
            ['name' => 'George Adekunle', 'email' => 'george@casi.org', 'phone' => '+234 801 111 0007', 'department' => 'Operations', 'designation' => 'Logistics Officer', 'status' => 'on_leave', 'join_date' => '2023-07-22', 'salary' => 380000, 'manager' => 'Patricia Mba'],
            ['name' => 'Helen Ogundimu', 'email' => 'helen@casi.org', 'phone' => '+234 801 111 0008', 'department' => 'Finance', 'designation' => 'Finance Manager', 'status' => 'active', 'join_date' => '2023-08-30', 'salary' => 520000, 'manager' => 'Daniel Okonkwo'],
            ['name' => 'Ibrahim Hassan', 'email' => 'ibrahim@casi.org', 'phone' => '+234 801 111 0009', 'department' => 'IT', 'designation' => 'IT Administrator', 'status' => 'active', 'join_date' => '2023-09-14', 'salary' => 480000, 'manager' => 'Daniel Okonkwo'],
            ['name' => 'Joy Adebayo', 'email' => 'joy@casi.org', 'phone' => '+234 801 111 0010', 'department' => 'Programs', 'designation' => 'Project Assistant', 'status' => 'active', 'join_date' => '2023-10-05', 'salary' => 370000, 'manager' => 'Samuel Eze'],
            ['name' => 'Kalu Chukwuma', 'email' => 'kalu@casi.org', 'phone' => '+234 801 111 0011', 'department' => 'Administration', 'designation' => 'Administrative Assistant', 'status' => 'active', 'join_date' => '2023-11-20', 'salary' => 280000, 'manager' => 'Adaeze Obi'],
            ['name' => 'Linda Ogbonna', 'email' => 'linda@casi.org', 'phone' => '+234 801 111 0012', 'department' => 'Communications', 'designation' => 'Communications Officer', 'status' => 'active', 'join_date' => '2024-01-08', 'salary' => 390000, 'manager' => 'Daniel Okonkwo'],
            ['name' => 'Mohammed Yusuf', 'email' => 'mohammed@casi.org', 'phone' => '+234 801 111 0013', 'department' => 'Programs', 'designation' => 'Project Assistant', 'status' => 'active', 'join_date' => '2024-02-14', 'salary' => 340000, 'manager' => 'Samuel Eze'],
            ['name' => 'Nneka Uche', 'email' => 'nneka@casi.org', 'phone' => '+234 801 111 0014', 'department' => 'HR', 'designation' => 'Recruitment Lead', 'status' => 'active', 'join_date' => '2024-03-01', 'salary' => 410000, 'manager' => 'Amina Bello'],
            ['name' => 'Oluwaseun Bakare', 'email' => 'seun@casi.org', 'phone' => '+234 801 111 0015', 'department' => 'Finance', 'designation' => 'Finance Officer', 'status' => 'active', 'join_date' => '2024-03-15', 'salary' => 400000, 'manager' => 'Helen Ogundimu'],
            ['name' => 'Patricia Mba', 'email' => 'patricia@casi.org', 'phone' => '+234 801 111 0016', 'department' => 'Operations', 'designation' => 'Operations Manager', 'status' => 'active', 'join_date' => '2024-04-01', 'salary' => 500000, 'manager' => 'Daniel Okonkwo'],
            ['name' => 'Rasheed Abdullahi', 'email' => 'rasheed@casi.org', 'phone' => '+234 801 111 0017', 'department' => 'IT', 'designation' => 'IT Administrator', 'status' => 'active', 'join_date' => '2024-04-20', 'salary' => 310000, 'manager' => 'Ibrahim Hassan'],
            ['name' => 'Sandra Okoro', 'email' => 'sandra@casi.org', 'phone' => '+234 801 111 0018', 'department' => 'Programs', 'designation' => 'Program Manager', 'status' => 'active', 'join_date' => '2024-05-10', 'salary' => 480000, 'manager' => 'Samuel Eze'],
            ['name' => 'Tunde Afolabi', 'email' => 'tunde@casi.org', 'phone' => '+234 801 111 0019', 'department' => 'Administration', 'designation' => 'Executive Director', 'status' => 'active', 'join_date' => '2024-06-01', 'salary' => 360000, 'manager' => 'Daniel Okonkwo'],
            ['name' => 'Ugochi Onyekachi', 'email' => 'ugochi@casi.org', 'phone' => '+234 801 111 0020', 'department' => 'Communications', 'designation' => 'Communications Officer', 'status' => 'on_leave', 'join_date' => '2024-06-15', 'salary' => 370000, 'manager' => 'Linda Ogbonna'],
            ['name' => 'Victor Emenike', 'email' => 'victor@casi.org', 'phone' => '+234 801 111 0021', 'department' => 'Finance', 'designation' => 'Finance Officer', 'status' => 'active', 'join_date' => '2024-07-01', 'salary' => 350000, 'manager' => 'Helen Ogundimu'],
            ['name' => 'Winifred Akinola', 'email' => 'winifred@casi.org', 'phone' => '+234 801 111 0022', 'department' => 'HR', 'designation' => 'HR Officer', 'status' => 'active', 'join_date' => '2024-07-20', 'salary' => 380000, 'manager' => 'Amina Bello'],
            ['name' => 'Xavier Osei', 'email' => 'xavier@casi.org', 'phone' => '+234 801 111 0023', 'department' => 'Programs', 'designation' => 'Project Assistant', 'status' => 'terminated', 'join_date' => '2024-08-05', 'salary' => 360000, 'manager' => 'Samuel Eze'],
            ['name' => 'Yetunde Balogun', 'email' => 'yetunde@casi.org', 'phone' => '+234 801 111 0024', 'department' => 'Operations', 'designation' => 'Logistics Officer', 'status' => 'active', 'join_date' => '2024-08-20', 'salary' => 390000, 'manager' => 'Patricia Mba'],
            ['name' => 'Zainab Abubakar', 'email' => 'zainab@casi.org', 'phone' => '+234 801 111 0025', 'department' => 'IT', 'designation' => 'Data Analyst', 'status' => 'active', 'join_date' => '2024-09-01', 'salary' => 430000, 'manager' => 'Ibrahim Hassan'],
        ];

        $staffNum = 1001;
        foreach ($employees as $emp) {
            $deptName = $emp['department'];
            $desigTitle = $emp['designation'];
            unset($emp['department'], $emp['designation']);

            $emp['department_id'] = $deptMap[$deptName];
            $emp['designation_id'] = $findDesig($desigTitle, $deptName);
            $emp['staff_id'] = 'CASI-' . str_pad($staffNum++, 4, '0', STR_PAD_LEFT);

            if ($emp['status'] === 'terminated') {
                $emp['termination_date'] = '2025-01-15';
            }

            Employee::updateOrCreate(
                ['email' => $emp['email']],
                $emp
            );
        }

        $this->command->info('HR seed data created: ' . count($departments) . ' departments, ' . count($designations) . ' designations, ' . count($employees) . ' employees.');
    }
}
