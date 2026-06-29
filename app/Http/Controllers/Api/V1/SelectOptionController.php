<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\SelectOption;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SelectOptionController extends ApiController
{
    private const LOCATION_GROUPS = ['country', 'bd_division', 'bd_district'];

    private const BULK_MAX_GROUPS = 60;

    private const BULK_MAX_CHILDREN = 30;

    /**
     * GET /api/v1/options/bulk?groups=religion,marital_status
     * GET /api/v1/options/bulk?children=country:5,caste:12
     *
     * Fetch many option lists in one round-trip. Public – cached 1 hour.
     */
    public function bulk(Request $request): JsonResponse
    {
        $groups = $this->parseGroupList($request->query('groups'));
        $children = $this->parseChildrenList($request->query('children'));

        if ($groups === [] && $children === []) {
            return response()->json(['groups' => (object) [], 'children' => (object) []]);
        }

        sort($groups);
        $childrenKey = collect($children)
            ->map(fn (array $child) => "{$child['group']}:{$child['parent_id']}")
            ->sort()
            ->implode(',');

        $cacheKey = $this->bulkCacheKey($groups, $childrenKey);

        $payload = Cache::remember($cacheKey, 3600, function () use ($groups, $children) {
            return [
                'groups' => $this->fetchTopLevelGroups($groups),
                'children' => $this->fetchChildGroups($children),
            ];
        });

        return response()->json($payload);
    }

    /**
     * GET /api/v1/options/{group}
     * GET /api/v1/options/{group}?parent_id=5
     *
     * Public – cached 1 hour
     */
    public function index(Request $request, string $group): JsonResponse
    {
        $parentId = $this->normalizeParentId($request->query('parent_id'));

        $cacheKey = "options:{$group}:parent:{$parentId}";

        $options = Cache::remember($cacheKey, 3600, function () use ($group, $parentId) {
            return $this->fetchOptions($group, $parentId);
        });

        return response()->json($options);
    }

    /**
     * @param  list<string>  $groups
     * @return array<string, list<array<string, mixed>>>
     */
    private function fetchTopLevelGroups(array $groups): array
    {
        if ($groups === []) {
            return [];
        }

        $normalizedGroups = array_map(
            fn (string $group) => $this->isLocationGroup($group) ? 'country' : $group,
            $groups
        );

        $rows = SelectOption::active()
            ->whereIn('group_key', array_values(array_unique($normalizedGroups)))
            ->whereNull('parent_id')
            ->orderBy('group_key')
            ->orderBy('sort_order')
            ->get(['id', 'group_key', 'value', 'label', 'metadata']);

        $grouped = [];
        foreach ($rows as $row) {
            $grouped[$row->group_key][] = [
                'id' => $row->id,
                'value' => $row->value,
                'label' => $row->label,
                'metadata' => $row->metadata,
            ];
        }

        $result = [];
        foreach ($groups as $group) {
            $normalized = $this->isLocationGroup($group) ? 'country' : $group;
            $result[$group] = $grouped[$normalized] ?? [];
        }

        return $result;
    }

    /**
     * @param  list<array{group: string, parent_id: int}>  $children
     * @return array<string, list<array<string, mixed>>>
     */
    private function fetchChildGroups(array $children): array
    {
        $result = [];

        foreach ($children as $child) {
            $key = "{$child['group']}:{$child['parent_id']}";
            $result[$key] = $this->fetchOptions($child['group'], $child['parent_id']);
        }

        return $result;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchOptions(string $group, int|string|null $parentId): array
    {
        $parentId = $this->normalizeParentId($parentId);
        $isLocationTreeRequest = $this->isLocationGroup($group);

        $query = SelectOption::active()->orderBy('sort_order');

        if ($isLocationTreeRequest && $parentId !== null) {
            return $query
                ->where('parent_id', $parentId)
                ->get(['id', 'value', 'label', 'metadata'])
                ->toArray();
        }

        $normalizedGroup = $isLocationTreeRequest ? 'country' : $group;

        return $query
            ->group($normalizedGroup)
            ->where('parent_id', $parentId)
            ->get(['id', 'value', 'label', 'metadata'])
            ->toArray();
    }

    /**
     * @return list<string>
     */
    private function parseGroupList(mixed $raw): array
    {
        if (! is_string($raw) || trim($raw) === '') {
            return [];
        }

        $groups = array_values(array_unique(array_filter(array_map(
            fn (string $group) => $this->sanitizeGroupKey($group),
            explode(',', $raw)
        ))));

        return array_slice($groups, 0, self::BULK_MAX_GROUPS);
    }

    /**
     * @return list<array{group: string, parent_id: int}>
     */
    private function parseChildrenList(mixed $raw): array
    {
        if (! is_string($raw) || trim($raw) === '') {
            return [];
        }

        $children = [];

        foreach (explode(',', $raw) as $pair) {
            $pair = trim($pair);
            if ($pair === '' || ! str_contains($pair, ':')) {
                continue;
            }

            [$group, $parentId] = explode(':', $pair, 2);
            $group = $this->sanitizeGroupKey($group);
            $parentId = filter_var($parentId, FILTER_VALIDATE_INT);

            if ($group === null || $parentId === false || $parentId <= 0) {
                continue;
            }

            $children[] = ['group' => $group, 'parent_id' => $parentId];
        }

        return array_slice($children, 0, self::BULK_MAX_CHILDREN);
    }

    private function sanitizeGroupKey(string $group): ?string
    {
        $group = strtolower(trim($group));

        return preg_match('/^[a-z0-9_]{1,64}$/', $group) ? $group : null;
    }

    private function normalizeParentId(mixed $parentId): ?int
    {
        if ($parentId === null || $parentId === '' || $parentId === 'null') {
            return null;
        }

        $parentId = filter_var($parentId, FILTER_VALIDATE_INT);

        return $parentId === false ? null : $parentId;
    }

    private function isLocationGroup(string $group): bool
    {
        return in_array($group, self::LOCATION_GROUPS, true);
    }

    /**
     * Short, fixed-length cache key for bulk payloads (avoids MySQL key column overflow).
     *
     * @param  list<string>  $groups
     */
    private function bulkCacheKey(array $groups, string $childrenKey): string
    {
        $fingerprint = implode(',', $groups) . '|' . $childrenKey;

        return 'options:bulk:' . hash('sha256', $fingerprint);
    }
}

