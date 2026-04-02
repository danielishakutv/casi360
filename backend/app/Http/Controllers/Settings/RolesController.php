<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RolesController extends Controller
{
    private const ROLES = [
        'super_admin' => 'Full system access with all permissions',
        'admin' => 'Administrative access with most permissions',
        'manager' => 'Department-level management access',
        'staff' => 'Standard staff access with limited permissions',
    ];

    public function index(): JsonResponse
    {
        $roleCounts = User::select('role', DB::raw('count(*) as user_count'))
            ->groupBy('role')
            ->pluck('user_count', 'role');

        $roles = collect(self::ROLES)->map(function ($description, $slug) use ($roleCounts) {
            return [
                'id' => $slug,
                'name' => str_replace('_', ' ', ucwords($slug, '_')),
                'slug' => $slug,
                'description' => $description,
                'user_count' => (int) ($roleCounts[$slug] ?? 0),
            ];
        })->values();

        return $this->success([
            'roles' => $roles,
        ]);
    }

    public function show(string $slug): JsonResponse
    {
        if (!array_key_exists($slug, self::ROLES)) {
            return $this->error('Role not found.', 404);
        }

        $userCount = User::where('role', $slug)->count();

        $users = User::where('role', $slug)
            ->select('id', 'name', 'email', 'role', 'status', 'created_at')
            ->orderBy('name')
            ->limit(50)
            ->get();

        return $this->success([
            'role' => [
                'id' => $slug,
                'name' => str_replace('_', ' ', ucwords($slug, '_')),
                'slug' => $slug,
                'description' => self::ROLES[$slug],
                'user_count' => $userCount,
                'users' => $users,
            ],
        ]);
    }
}
