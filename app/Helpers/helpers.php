<?php

use App\Models\Profile;
use App\Models\SelectOption;
use App\Services\CloudflareImageService;

if (!function_exists('json_list')) {
    function json_list($value, $separator = ', ')
    {
        if (empty($value)) {
            return '—';
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                $value = $decoded;
            }
        }

        if (is_array($value)) {
            return implode($separator, array_map(function ($item) {
                return ucwords(str_replace('_', ' ', (string) $item));
            }, $value));
        }

        return (string) $value;
    }
}

function humanize($value)
{
    return $value
        ? ucwords(str_replace(['_', '-'], ' ', $value))
        : '—';
}

if (! function_exists('option_label')) {
    function option_label(?string $groupKey, ?string $value, ?int $parentId = null): string
    {
        if (! $value) {
            return '—';
        }

        static $cache = [];

        $cacheKey = ($groupKey ?? '*').':'.($parentId ?? 'null').':'.$value;

        if (! array_key_exists($cacheKey, $cache)) {
            $query = SelectOption::query()->where('value', $value);

            if ($groupKey) {
                $query->where('group_key', $groupKey);
            }

            if ($parentId !== null) {
                $query->where('parent_id', $parentId);
            }

            $cache[$cacheKey] = $query->value('label');
        }

        return $cache[$cacheKey] ?: humanize($value);
    }
}

if (! function_exists('option_labels')) {
    function option_labels(?string $groupKey, mixed $values, string $separator = ', '): string
    {
        if (empty($values)) {
            return '—';
        }

        if (is_string($values)) {
            $decoded = json_decode($values, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                $values = $decoded;
            }
        }

        if (! is_array($values)) {
            return option_label($groupKey, (string) $values);
        }

        $labels = array_map(
            fn ($item) => option_label($groupKey, (string) $item),
            $values
        );

        return $labels ? implode($separator, $labels) : '—';
    }
}

if (! function_exists('country_option_labels')) {
    function country_option_labels(mixed $values, string $separator = ', '): string
    {
        if (empty($values)) {
            return '—';
        }

        if (is_string($values)) {
            $decoded = json_decode($values, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                $values = $decoded;
            }
        }

        if (! is_array($values)) {
            $values = [$values];
        }

        $labels = array_map(function ($value) {
            $label = SelectOption::query()
                ->where('group_key', 'country')
                ->where('value', (string) $value)
                ->value('label');

            return $label ?: humanize((string) $value);
        }, $values);

        return $labels ? implode($separator, $labels) : '—';
    }
}

if (! function_exists('profile_location_fields')) {
    /**
     * @return array<int, array{label: string, value: string}>
     */
    function profile_location_fields(?Profile $profile): array
    {
        if (! $profile || ! $profile->country) {
            return [];
        }

        $countryOption = SelectOption::query()
            ->where('group_key', 'country')
            ->where('value', $profile->country)
            ->whereNull('parent_id')
            ->first(['id', 'label', 'metadata']);

        if (! $countryOption) {
            return [
                ['label' => 'Country', 'value' => option_label('country', $profile->country)],
            ];
        }

        $fields = [
            ['label' => 'Country', 'value' => $countryOption->label],
        ];

        $metadata = $countryOption->metadata ?? [];
        $fieldMap = $metadata['field_map'] ?? [];
        $parentId = $countryOption->id;

        $levelConfig = [
            'level_2' => ['label_key' => 'level_2_label', 'default' => 'Region'],
            'level_3' => ['label_key' => 'level_3_label', 'default' => 'City'],
            'level_4' => ['label_key' => 'level_4_label', 'default' => 'Area'],
        ];

        foreach (['level_2', 'level_3', 'level_4'] as $level) {
            $profileField = $fieldMap[$level] ?? null;

            if (! $profileField || ! $parentId) {
                continue;
            }

            $storedValue = $profile->{$profileField} ?? null;

            if (! $storedValue) {
                continue;
            }

            $fields[] = [
                'label' => $metadata[$levelConfig[$level]['label_key']] ?? $levelConfig[$level]['default'],
                'value' => option_label('country', $storedValue, $parentId),
            ];

            $childOption = SelectOption::query()
                ->where('group_key', 'country')
                ->where('value', $storedValue)
                ->where('parent_id', $parentId)
                ->first(['id']);

            $parentId = $childOption?->id;
        }

        return $fields;
    }
}


function cfImage(string $imageId): string
{
    return app(CloudflareImageService::class)->deliveryUrl($imageId, 'public');
}

function profilePhotoUrl(?string $filePath): ?string
{
    if (! $filePath) {
        return null;
    }

    return app(CloudflareImageService::class)->deliveryUrl($filePath, 'public');
}
