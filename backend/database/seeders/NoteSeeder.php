<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\Note;
use App\Models\User;
use Illuminate\Database\Seeder;

class NoteSeeder extends Seeder
{
    public function run(): void
    {
        // Get some employees and a user to act as note creator
        $employees = Employee::where('status', 'active')->limit(10)->get();
        $adminUser = User::where('role', 'super_admin')->first();

        if ($employees->isEmpty() || !$adminUser) {
            $this->command->warn('NoteSeeder: No active employees or admin user found. Skipping.');
            return;
        }

        $notes = [
            ['title' => 'Performance Review Q1 2026', 'content' => 'Strong performance across all KPIs. Exceeded targets in community outreach and program delivery. Recommended for commendation.', 'type' => 'performance', 'priority' => 'high'],
            ['title' => 'Training Completion — Data Analysis', 'content' => 'Successfully completed the 40-hour data analysis certification program. Skills are now being applied to project monitoring dashboards.', 'type' => 'training', 'priority' => 'medium'],
            ['title' => 'Late Attendance — January 2026', 'content' => 'Arrived late on 3 separate occasions in January. Verbal warning issued. Employee acknowledged and committed to improvement.', 'type' => 'disciplinary', 'priority' => 'medium'],
            ['title' => 'Outstanding Field Work — Abuja Project', 'content' => 'Demonstrated exceptional dedication during the Abuja community health project. Worked overtime to ensure all beneficiaries were reached.', 'type' => 'commendation', 'priority' => 'high'],
            ['title' => 'Medical Leave — February 2026', 'content' => 'Approved medical leave from February 10-17, 2026. Doctor note on file. Employee returned to full duties on February 18.', 'type' => 'medical', 'priority' => 'low'],
            ['title' => 'Team Meeting Notes — March 2026', 'content' => 'Participated actively in the quarterly planning session. Contributed valuable insights on improving field data collection processes.', 'type' => 'general', 'priority' => 'low'],
            ['title' => 'Probation Review', 'content' => 'Completed 6-month probationary period. Work quality is satisfactory. Recommended for confirmation as permanent staff.', 'type' => 'performance', 'priority' => 'high'],
            ['title' => 'Skills Gap Assessment', 'content' => 'Identified need for advanced Excel and reporting skills. Enrolled in upcoming training session scheduled for April 2026.', 'type' => 'training', 'priority' => 'medium'],
            ['title' => 'Conflict Resolution — Peer Issue', 'content' => 'Mediated a minor workplace conflict with a colleague. Both parties agreed on resolution steps. Follow-up meeting scheduled in two weeks.', 'type' => 'general', 'priority' => 'medium'],
            ['title' => 'Annual Performance Summary 2025', 'content' => 'Overall rating: Meets Expectations. Areas of strength include teamwork and reliability. Development area: public speaking and presentation skills.', 'type' => 'performance', 'priority' => 'high'],
            ['title' => 'Safety Training Completed', 'content' => 'Completed mandatory workplace safety and first-aid training. Certificate valid until March 2028.', 'type' => 'training', 'priority' => 'low'],
            ['title' => 'Commendation — Donor Event Support', 'content' => 'Played a key role in organizing the annual donor appreciation dinner. Event received positive feedback from all attendees.', 'type' => 'commendation', 'priority' => 'medium'],
        ];

        $count = 0;
        foreach ($notes as $i => $noteData) {
            $employee = $employees[$i % $employees->count()];

            Note::updateOrCreate(
                ['title' => $noteData['title'], 'employee_id' => $employee->id],
                array_merge($noteData, [
                    'employee_id' => $employee->id,
                    'created_by' => $adminUser->id,
                ])
            );
            $count++;
        }

        $this->command->info("Note seed data created: {$count} notes.");
    }
}
