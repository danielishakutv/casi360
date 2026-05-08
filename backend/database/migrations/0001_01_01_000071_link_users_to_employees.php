<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Unifies authentication identity with HR identity. Until this
 * migration users and employees were two parallel tables that only
 * happened to share an email column — registering a user did NOT
 * create an HR record, and existing super-admin / admin / staff
 * users were invisible to the HR module.
 *
 * Three changes:
 *   1. Add a nullable, unique `user_id` FK to employees so each
 *      user has at most one matching employee row.
 *   2. Relax department_id and designation_id to NULLABLE. Users
 *      auto-created from the User booted-hook don't yet know
 *      which department they belong to; HR can fill that in later
 *      via the employees page. (Existing rows already have these
 *      set, so no data is lost.)
 *   3. Backfill: walk every existing user, link any matching
 *      employee by email, and create a fresh employee for every
 *      user that doesn't yet have one. Super-admin definitely.
 *
 * Idempotent: column adds and ALTERs are guarded; backfill skips
 * users that already have an employee row.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Add user_id FK ──────────────────────────────────
        if (!Schema::hasColumn('employees', 'user_id')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->foreignUuid('user_id')
                      ->nullable()
                      ->after('id')
                      ->constrained('users')
                      ->nullOnDelete();
                $table->unique('user_id', 'employees_user_id_unique');
            });
        }

        // ── 2. Relax department_id / designation_id to NULL ────
        // MySQL keeps the existing FK when we just MODIFY the
        // column nullability — no need to drop/recreate.
        DB::statement('ALTER TABLE employees MODIFY COLUMN department_id CHAR(36) NULL');
        DB::statement('ALTER TABLE employees MODIFY COLUMN designation_id CHAR(36) NULL');

        // ── 3. Backfill: every user gets an employee row ───────
        $this->backfillEmployeesForExistingUsers();
    }

    public function down(): void
    {
        // Tighten the nullables back to NOT NULL — only safe if no
        // null rows exist, so we delete the auto-created rows
        // (those linked via user_id with no department/designation)
        // before re-applying the constraint.
        DB::table('employees')
            ->whereNotNull('user_id')
            ->whereNull('department_id')
            ->whereNull('designation_id')
            ->delete();

        if (Schema::hasColumn('employees', 'user_id')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
                $table->dropUnique('employees_user_id_unique');
                $table->dropColumn('user_id');
            });
        }

        DB::statement('ALTER TABLE employees MODIFY COLUMN department_id CHAR(36) NOT NULL');
        DB::statement('ALTER TABLE employees MODIFY COLUMN designation_id CHAR(36) NOT NULL');
    }

    private function backfillEmployeesForExistingUsers(): void
    {
        $users = DB::table('users')->select('id', 'name', 'email', 'phone', 'status')->get();

        foreach ($users as $user) {
            // Already linked? Skip.
            $linked = DB::table('employees')->where('user_id', $user->id)->exists();
            if ($linked) {
                continue;
            }

            // Same email already employed? Just link it.
            $byEmail = DB::table('employees')->where('email', $user->email)->first();
            if ($byEmail) {
                DB::table('employees')->where('id', $byEmail->id)->update([
                    'user_id'    => $user->id,
                    'updated_at' => now(),
                ]);
                continue;
            }

            // Otherwise create a fresh employee row.
            DB::table('employees')->insert([
                'id'             => (string) Str::uuid(),
                'user_id'        => $user->id,
                'staff_id'       => $this->generateStaffId(),
                'name'           => $user->name,
                'email'          => $user->email,
                'phone'          => $user->phone,
                'department_id'  => null,
                'designation_id' => null,
                'manager'        => null,
                'status'         => $user->status === 'active' ? 'active' : 'terminated',
                'join_date'      => now()->toDateString(),
                'salary'         => 0,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
        }
    }

    /**
     * Sequential CASI-NNNN staff id, picking up from whatever's
     * currently in the table. Matches the pattern HRSeeder /
     * DemoEmployeesSeeder use so we don't fork the numbering.
     */
    private function generateStaffId(): string
    {
        $last = DB::table('employees')
            ->where('staff_id', 'like', 'CASI-%')
            ->orderByDesc('staff_id')
            ->value('staff_id');
        $lastNum = $last ? (int) substr($last, 5) : 1000;
        return 'CASI-' . str_pad($lastNum + 1, 4, '0', STR_PAD_LEFT);
    }
};
