<?php

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


function cfImage(string $imageId, string $varient = 'public')
{
    $clouflareImageService = app(CloudflareImageService::class);
    return $clouflareImageService->deliveryUrl($imageId, $varient);
}

function profilePhotoUrl(?string $filePath): ?string
{
    if (! $filePath) {
        return null;
    }

    return app(\App\Services\ProfilePhotoStorageService::class)->url($filePath);
}
