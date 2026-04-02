<?php

namespace Database\Seeders;

use App\Models\Forum;
use App\Models\Notice;
use App\Models\NoticeAudience;
use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CommunicationSeeder extends Seeder
{
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | 1. Create General Forum
        |--------------------------------------------------------------------------
        */
        $generalForum = Forum::updateOrCreate(
            ['type' => 'general', 'name' => 'General'],
            [
                'id'          => Str::uuid()->toString(),
                'description' => 'Organisation-wide discussion forum open to all staff.',
                'status'      => 'active',
            ]
        );

        $this->command->info("General forum created/verified: {$generalForum->name}");

        /*
        |--------------------------------------------------------------------------
        | 2. Create Department Forums (one per existing department)
        |--------------------------------------------------------------------------
        */
        $departments = Department::where('status', 'active')->get();
        $deptCount   = 0;

        foreach ($departments as $department) {
            Forum::updateOrCreate(
                ['type' => 'department', 'department_id' => $department->id],
                [
                    'id'          => Str::uuid()->toString(),
                    'name'        => $department->name,
                    'description' => "Discussion forum for the {$department->name} department.",
                    'status'      => 'active',
                ]
            );
            $deptCount++;
        }

        $this->command->info("Department forums created/verified: {$deptCount}");

        /*
        |--------------------------------------------------------------------------
        | 3. Sample Notice (welcome)
        |--------------------------------------------------------------------------
        */
        $admin = User::where('role', 'super_admin')->first()
              ?? User::where('role', 'admin')->first();

        if ($admin) {
            $notice = Notice::updateOrCreate(
                ['title' => 'Welcome to CASI 360'],
                [
                    'id'           => Str::uuid()->toString(),
                    'author_id'    => $admin->id,
                    'body'         => "Welcome to the CASI 360 platform!\n\nThis is the official communication hub for all staff. Use this space to:\n\n- Check important organisational notices\n- Communicate with colleagues via direct messages\n- Participate in department and general forums\n\nPlease read all pinned notices carefully.",
                    'priority'     => 'important',
                    'status'       => 'published',
                    'publish_date' => now()->toDateString(),
                    'expiry_date'  => null,
                    'is_pinned'    => true,
                ]
            );

            // Target all users
            NoticeAudience::updateOrCreate(
                ['notice_id' => $notice->id, 'audience_type' => 'all'],
                [
                    'id' => Str::uuid()->toString(),
                ]
            );

            $this->command->info("Welcome notice created/verified.");
        }

        $this->command->info("Communication seeder complete.");
    }
}
