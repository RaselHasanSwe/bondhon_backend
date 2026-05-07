<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\SelectOption;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SelectOptionController extends ApiController
{
    /**
     * GET /api/v1/options/{group}
     * GET /api/v1/options/{group}?parent_id=5
     *
     * Public – cached 1 hour
     */
    public function index(Request $request, string $group): JsonResponse
    {
        $parentId = $request->query('parent_id'); // null = top-level

        // Normalise: empty string → null so cache key is consistent
        if ($parentId === '' || $parentId === 'null') {
            $parentId = null;
        }

        $cacheKey = "options:{$group}:parent:{$parentId}";

        $options = Cache::remember($cacheKey, 3600, function () use ($group, $parentId) {
            return SelectOption::active()
                ->group($group)
                ->where('parent_id', $parentId)
                ->orderBy('sort_order')
                ->get(['id', 'value', 'label', 'metadata'])
                ->toArray();
        });

        return response()->json($options);
    }
}

