<?php

namespace App\Services;

use App\Models\SelectOption;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use RuntimeException;

class OptionsDataLoader
{
    private const DATA_PATH = 'database/data/options';

    /** @var list<string> */
    private array $touchedCacheKeys = [];

    /** @var array{created: int, updated: int, deactivated: int} */
    private array $stats = ['created' => 0, 'updated' => 0, 'deactivated' => 0];

    public function dataPath(): string
    {
        return base_path(self::DATA_PATH);
    }

    /**
     * @return list<string>
     */
    public function listDataFiles(?string $only = null): array
    {
        $base = $this->dataPath();

        if ($only !== null) {
            $path = str_contains($only, '/') ? "{$base}/{$only}" : "{$base}/{$only}";

            if (! File::exists($path)) {
                throw new RuntimeException("Options data file not found: {$only}");
            }

            return [$path];
        }

        $files = [];
        foreach (['*.json', 'locations/*.json'] as $pattern) {
            foreach (File::glob("{$base}/{$pattern}") ?: [] as $file) {
                $files[] = $file;
            }
        }

        sort($files);

        return $files;
    }

    /**
     * @return array{created: int, updated: int, deactivated: int}
     */
    public function syncFile(string $path, bool $dryRun = false): array
    {
        $this->resetStats();

        $data = json_decode(File::get($path), true, 512, JSON_THROW_ON_ERROR);

        if (isset($data['group_key'], $data['options']) && is_array($data['options'])) {
            $this->syncFlatGroup($data['group_key'], $data['options'], null, $dryRun);
            $this->deactivateMissingInGroup($data['group_key'], null, $dryRun);
        } elseif (isset($data['group_key'], $data['entry']) && is_array($data['entry'])) {
            $groupKey = $data['group_key'];
            $syncedIds = [];
            $this->syncNode($groupKey, $data['entry'], null, $syncedIds, $dryRun);
            $this->deactivateMissingSubtree($groupKey, $syncedIds, $dryRun);
        } else {
            throw new RuntimeException("Invalid options data format in {$path}");
        }

        if (! $dryRun) {
            $this->flushCaches();
        }

        return $this->stats;
    }

    /**
     * @return array{created: int, updated: int, deactivated: int}
     */
    public function syncAll(?string $group = null, ?string $only = null, bool $dryRun = false): array
    {
        $this->resetStats();
        $files = $this->resolveFiles($group, $only);

        foreach ($files as $file) {
            $fileStats = $this->syncFile($file, $dryRun);
            $this->stats['created'] += $fileStats['created'];
            $this->stats['updated'] += $fileStats['updated'];
            $this->stats['deactivated'] += $fileStats['deactivated'];
        }

        return $this->stats;
    }

    /**
     * @return list<string>
     */
    private function resolveFiles(?string $group, ?string $only): array
    {
        if ($only !== null) {
            return $this->listDataFiles($only);
        }

        if ($group !== null) {
            $flat = "{$this->dataPath()}/{$group}.json";
            if (File::exists($flat)) {
                return [$flat];
            }

            throw new RuntimeException("No data file found for group: {$group}");
        }

        return $this->listDataFiles();
    }

    /**
     * @param  list<array<string, mixed>>  $options
     * @param  list<int>  $syncedIds
     */
    private function syncFlatGroup(string $groupKey, array $options, ?int $parentId, bool $dryRun, array &$syncedIds = []): void
    {
        foreach ($options as $index => $option) {
            $sortOrder = $option['sort_order'] ?? ($index + 1);
            $children = $option['children'] ?? [];
            unset($option['children']);

            $record = $this->upsertOption(
                $groupKey,
                $option['value'],
                $option['label'],
                $parentId,
                $option['metadata'] ?? null,
                (int) $sortOrder,
                $dryRun,
            );

            if ($record !== null) {
                $syncedIds[] = $record->id;
            }

            if ($children !== []) {
                $this->syncFlatGroup($groupKey, $children, $record?->id, $dryRun, $syncedIds);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $node
     * @param  list<int>  $syncedIds
     */
    private function syncNode(string $groupKey, array $node, ?int $parentId, array &$syncedIds, bool $dryRun, int $sortOrder = 1): void
    {
        $children = $node['children'] ?? [];
        $metadata = $node['metadata'] ?? null;

        $record = $this->upsertOption(
            $groupKey,
            $node['value'],
            $node['label'],
            $parentId,
            $metadata,
            $node['sort_order'] ?? $sortOrder,
            $dryRun,
        );

        if ($record !== null) {
            $syncedIds[] = $record->id;
        }

        foreach ($children as $index => $child) {
            $this->syncNode($groupKey, $child, $record?->id, $syncedIds, $dryRun, $index + 1);
        }
    }

    private function upsertOption(
        string $groupKey,
        string $value,
        string $label,
        ?int $parentId,
        ?array $metadata,
        int $sortOrder,
        bool $dryRun,
    ): ?SelectOption {
        $existing = SelectOption::query()
            ->where('group_key', $groupKey)
            ->where('value', $value)
            ->where('parent_id', $parentId)
            ->first();

        $payload = [
            'label' => $label,
            'metadata' => $metadata,
            'sort_order' => $sortOrder,
            'is_active' => true,
        ];

        $this->trackCacheKey($groupKey, $parentId);

        if ($dryRun) {
            if ($existing === null) {
                $this->stats['created']++;
            } elseif ($this->optionChanged($existing, $payload)) {
                $this->stats['updated']++;
            }

            return $existing;
        }

        if ($existing === null) {
            $this->stats['created']++;

            return SelectOption::create([
                'group_key' => $groupKey,
                'parent_id' => $parentId,
                'value' => $value,
                ...$payload,
            ]);
        }

        if ($this->optionChanged($existing, $payload)) {
            $existing->update($payload);
            $this->stats['updated']++;
        }

        return $existing->fresh();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function optionChanged(SelectOption $existing, array $payload): bool
    {
        return $existing->label !== $payload['label']
            || $existing->sort_order !== $payload['sort_order']
            || $existing->is_active !== $payload['is_active']
            || $existing->metadata != $payload['metadata'];
    }

    private function deactivateMissingInGroup(string $groupKey, ?int $parentId, bool $dryRun): void
    {
        // Only deactivate top-level flat groups (no parent).
        if ($parentId !== null) {
            return;
        }

        $active = SelectOption::query()
            ->where('group_key', $groupKey)
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->get();

        $syncedValues = collect(
            json_decode(File::get("{$this->dataPath()}/{$groupKey}.json"), true)['options'] ?? [],
        )->pluck('value')->all();

        foreach ($active as $option) {
            if (! in_array($option->value, $syncedValues, true)) {
                if (! $dryRun) {
                    $option->update(['is_active' => false]);
                }
                $this->stats['deactivated']++;
                $this->trackCacheKey($groupKey, null);
            }
        }
    }

    /**
     * @param  list<int>  $syncedIds
     */
    private function deactivateMissingSubtree(string $groupKey, array $syncedIds, bool $dryRun): void
    {
        if ($syncedIds === []) {
            return;
        }

        $rootId = $syncedIds[0];

        $toDeactivate = SelectOption::query()
            ->where('group_key', $groupKey)
            ->where('is_active', true)
            ->whereNotIn('id', $syncedIds)
            ->where(function ($query) use ($rootId) {
                $query->where('id', $rootId)
                    ->orWhere('parent_id', $rootId)
                    ->orWhereHas('parent', fn ($q) => $q->where('parent_id', $rootId));
            })
            ->get();

        // Deactivate any active country subtree nodes not present in synced set.
        $allInTree = $this->collectSubtreeIds($rootId);
        $missing = SelectOption::query()
            ->where('group_key', $groupKey)
            ->where('is_active', true)
            ->whereIn('id', $allInTree)
            ->whereNotIn('id', $syncedIds)
            ->get();

        foreach ($missing as $option) {
            if (! $dryRun) {
                $option->update(['is_active' => false]);
            }
            $this->stats['deactivated']++;
            $this->trackCacheKey($groupKey, $option->parent_id);
        }
    }

    /**
     * @return list<int>
     */
    private function collectSubtreeIds(int $rootId): array
    {
        $ids = [$rootId];
        $queue = [$rootId];

        while ($queue !== []) {
            $parentId = array_shift($queue);
            $children = SelectOption::query()
                ->where('parent_id', $parentId)
                ->pluck('id')
                ->all();

            foreach ($children as $childId) {
                $ids[] = $childId;
                $queue[] = $childId;
            }
        }

        return $ids;
    }

    private function trackCacheKey(string $groupKey, ?int $parentId): void
    {
        $this->touchedCacheKeys[] = "options:{$groupKey}:parent:".($parentId ?? 'null');
    }

    private function flushCaches(): void
    {
        foreach (array_unique($this->touchedCacheKeys) as $key) {
            Cache::forget($key);
        }

        Cache::flush();
    }

    private function resetStats(): void
    {
        $this->stats = ['created' => 0, 'updated' => 0, 'deactivated' => 0];
        $this->touchedCacheKeys = [];
    }
}
