<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\OptionGroupConfig;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class OptionGroupController extends ApiController
{
    /**
     * GET /api/v1/option-groups
     * Returns all active group configs, optionally filtered by ?tab=religion
     * Cached 1 hour.
     */
    public function index(\Illuminate\Http\Request $request): JsonResponse
    {
        $tab = $request->query('tab');
        $cacheKey = 'option_groups:tab:' . ($tab ?? 'all');

        $groups = Cache::remember($cacheKey, 3600, function () use ($tab) {
            $query = OptionGroupConfig::active()->orderBy('sort_order');
            if ($tab) {
                $query->where('profile_tab', $tab);
            }
            return $query->get([
                'group_key', 'label', 'profile_tab', 'field_name',
                'input_type', 'parent_group_key', 'max_nesting_depth', 'sort_order',
            ])->toArray();
        });

        return response()->json($groups);
    }
}

