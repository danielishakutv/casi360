<?php

namespace App\Http\Controllers\Projects;

use App\Http\Controllers\Controller;
use App\Http\Requests\Projects\StoreBudgetCategoryRequest;
use App\Http\Requests\Projects\UpdateBudgetCategoryRequest;
use App\Models\AuditLog;
use App\Models\BudgetCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BudgetCategoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = BudgetCategory::query();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $search = str_replace(['%', '_'], ['\%', '\_'], $request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $sortBy = $request->input('sort_by', 'sort_order');
        $sortDir = $request->input('sort_dir', 'asc');
        $allowedSorts = ['name', 'sort_order', 'status', 'created_at'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDir === 'desc' ? 'desc' : 'asc');
        }

        $perPage = min((int) $request->input('per_page', 25), 100);

        if ($perPage == 0) {
            $categories = $query->get();
            return $this->success([
                'budget_categories' => $categories->map->toApiArray(),
                'meta' => ['total' => $categories->count()],
            ]);
        }

        $paginated = $query->paginate($perPage);

        return $this->success([
            'budget_categories' => collect($paginated->items())->map->toApiArray(),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
        ]);
    }

    public function store(StoreBudgetCategoryRequest $request): JsonResponse
    {
        return DB::transaction(function () use ($request) {
            $category = BudgetCategory::create($request->validated());

            AuditLog::record(
                auth()->id(),
                'budget_category_created',
                'budget_category',
                $category->id,
                null,
                $category->toApiArray()
            );

            return $this->success([
                'budget_category' => $category->toApiArray(),
            ], 'Budget category created successfully', 201);
        });
    }

    public function show(string $id): JsonResponse
    {
        $category = BudgetCategory::findOrFail($id);
        return $this->success(['budget_category' => $category->toApiArray()]);
    }

    public function update(UpdateBudgetCategoryRequest $request, string $id): JsonResponse
    {
        $category = BudgetCategory::findOrFail($id);
        $oldValues = $category->toApiArray();

        return DB::transaction(function () use ($request, $category, $oldValues) {
            $category->update($request->validated());

            AuditLog::record(
                auth()->id(),
                'budget_category_updated',
                'budget_category',
                $category->id,
                $oldValues,
                $category->fresh()->toApiArray()
            );

            return $this->success([
                'budget_category' => $category->fresh()->toApiArray(),
            ], 'Budget category updated successfully');
        });
    }

    public function destroy(string $id): JsonResponse
    {
        $category = BudgetCategory::findOrFail($id);

        $budgetLineCount = $category->budgetLines()->count();
        if ($budgetLineCount > 0) {
            return $this->error(
                'Cannot delete budget category that is used in project budget lines. Remove or reassign those lines first.',
                422
            );
        }

        return DB::transaction(function () use ($category, $id) {
            $categoryData = $category->toApiArray();
            $category->delete();

            AuditLog::record(
                auth()->id(),
                'budget_category_deleted',
                'budget_category',
                $id,
                $categoryData,
                null
            );

            return $this->success(null, 'Budget category deleted successfully');
        });
    }
}
