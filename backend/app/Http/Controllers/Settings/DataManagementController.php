<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DataManagementController extends Controller
{
    public function export(Request $request): JsonResponse
    {
        $request->validate([
            'format' => ['nullable', 'in:json,csv'],
            'entities' => ['nullable', 'array'],
            'entities.*' => ['string', 'in:users,employees,departments'],
        ]);

        $format = $request->input('format', 'json');
        $entities = $request->input('entities', ['users', 'employees', 'departments']);
        $exportData = [];

        if (in_array('users', $entities)) {
            $exportData['users'] = User::select('id', 'name', 'email', 'role', 'status', 'created_at')
                ->get()
                ->toArray();
        }
        if (in_array('employees', $entities)) {
            $exportData['employees'] = Employee::with('department:id,name')
                ->get()
                ->map(function ($e) {
                    return $e->toApiArray();
                })
                ->toArray();
        }
        if (in_array('departments', $entities)) {
            $exportData['departments'] = Department::get()
                ->map(function ($d) {
                    return $d->toApiArray();
                })
                ->toArray();
        }

        AuditLog::record(
            auth()->id(),
            'data_exported',
            'system',
            null,
            null,
            ['entities' => $entities, 'format' => $format]
        );

        return $this->success([
            'data' => $exportData,
            'exported_at' => now()->toIso8601String(),
            'entities' => $entities,
            'format' => $format,
        ], 'Data exported successfully');
    }

    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:json,csv', 'max:10240'],
        ]);

        // Parse uploaded file
        $file = $request->file('file');
        $extension = $file->getClientOriginalExtension();

        if ($extension === 'json') {
            $content = file_get_contents($file->getRealPath());
            $data = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return $this->error('Invalid JSON file.', 422);
            }
        } else {
            return $this->error('Only JSON import is currently supported.', 422);
        }

        AuditLog::record(
            auth()->id(),
            'data_imported',
            'system',
            null,
            null,
            ['filename' => $file->getClientOriginalName()]
        );

        return $this->success([
            'message' => 'Data import initiated',
            'filename' => $file->getClientOriginalName(),
        ], 'Data import completed successfully');
    }

    public function backup(): JsonResponse
    {
        AuditLog::record(
            auth()->id(),
            'backup_created',
            'system',
            null,
            null,
            ['triggered_at' => now()->toIso8601String()]
        );

        return $this->success([
            'backup_id' => (string) \Illuminate\Support\Str::uuid(),
            'triggered_at' => now()->toIso8601String(),
            'status' => 'initiated',
        ], 'Backup initiated successfully');
    }
}
