<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\ApiController;
use App\Models\SelectOption;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;

class AdminSelectOptionController extends ApiController
{
    /**
     * GET /api/v1/admin/select-options
     * List all groups (top-level) with optional filter by group_key
     */
    public function index(Request $request): JsonResponse
    {
        $query = SelectOption::with('children')->topLevel()->orderBy('group_key')->orderBy('sort_order');

        if ($request->filled('group_key')) {
            $query->group($request->group_key);
        }

        $options = $query->paginate(100);

        return response()->json($options);
    }

    /**
     * GET /api/v1/admin/select-options/groups
     * Returns distinct group_key list
     */
    public function groups(): JsonResponse
    {
        $groups = SelectOption::select('group_key')
            ->distinct()
            ->orderBy('group_key')
            ->pluck('group_key');

        return response()->json($groups);
    }

    /**
     * POST /api/v1/admin/select-options
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'group_key'  => 'required|string|max:60',
            'parent_id'  => 'nullable|exists:select_options,id',
            'value'      => 'required|string|max:100',
            'label'      => 'required|string|max:255',
            'metadata'   => 'nullable|array',
            'sort_order' => 'integer',
            'is_active'  => 'boolean',
        ]);

        // Enforce uniqueness manually for clarity
        $exists = SelectOption::where('group_key', $validated['group_key'])
            ->where('value', $validated['value'])
            ->where('parent_id', $validated['parent_id'] ?? null)
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'A duplicate option already exists for this group/value/parent combination.'], 422);
        }

        $option = SelectOption::create($validated);

        $this->clearCache($validated['group_key'], $validated['parent_id'] ?? null);

        return response()->json($option, 201);
    }

    /**
     * GET /api/v1/admin/select-options/{id}
     */
    public function show(SelectOption $selectOption): JsonResponse
    {
        return response()->json($selectOption->load('children'));
    }

    /**
     * PUT /api/v1/admin/select-options/{id}
     */
    public function update(Request $request, SelectOption $selectOption): JsonResponse
    {
        $validated = $request->validate([
            'group_key'  => 'sometimes|string|max:60',
            'parent_id'  => 'nullable|exists:select_options,id',
            'value'      => 'sometimes|string|max:100',
            'label'      => 'sometimes|string|max:255',
            'metadata'   => 'nullable|array',
            'sort_order' => 'integer',
            'is_active'  => 'boolean',
        ]);

        $oldGroupKey  = $selectOption->group_key;
        $oldParentId  = $selectOption->parent_id;

        $selectOption->update($validated);

        $this->clearCache($oldGroupKey, $oldParentId);
        $this->clearCache($selectOption->group_key, $selectOption->parent_id);

        return response()->json($selectOption);
    }

    /**
     * DELETE /api/v1/admin/select-options/{id}
     * Children are auto-deleted by ON DELETE CASCADE
     */
    public function destroy(SelectOption $selectOption): JsonResponse
    {
        $groupKey = $selectOption->group_key;
        $parentId = $selectOption->parent_id;

        $selectOption->delete();

        $this->clearCache($groupKey, $parentId);

        return response()->json(null, 204);
    }

    /**
     * PUT /api/v1/admin/select-options/{id}/toggle
     * Toggle is_active
     */
    public function toggle(SelectOption $selectOption): JsonResponse
    {
        $selectOption->update(['is_active' => !$selectOption->is_active]);

        $this->clearCache($selectOption->group_key, $selectOption->parent_id);

        return response()->json($selectOption);
    }

    // ── Private Helpers ──────────────────────────────────────────────

    private function clearCache(string $groupKey, ?int $parentId): void
    {
        Cache::forget("options:{$groupKey}:parent:{$parentId}");
    }
}

