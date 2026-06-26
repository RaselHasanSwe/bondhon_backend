<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ProfilePhotoStorageService
{
    public function __construct(
        private readonly CloudflareImageService $cloudflare,
    ) {}

    public function usesCloudflare(): bool
    {
        return (bool) config('cloudflare.profile_photos_enabled');
    }

    /**
     * @return array{path: string, url: string}
     */
    public function store(string $jpegContents, int $userId): array
    {
        if ($this->usesCloudflare()) {
            return $this->storeOnCloudflare($jpegContents, $userId);
        }

        return $this->storeLocally($jpegContents, $userId);
    }

    public function delete(?string $filePath): void
    {
        if (! $filePath) {
            return;
        }

        if (Storage::disk('public')->exists($filePath)) {
            Storage::disk('public')->delete($filePath);

            return;
        }

        $this->cloudflare->delete($filePath);
    }

    public function url(?string $filePath): ?string
    {
        if (! $filePath) {
            return null;
        }

        if (str_starts_with($filePath, 'http')) {
            return $filePath;
        }

        if (Storage::disk('public')->exists($filePath)) {
            return Storage::disk('public')->url($filePath);
        }

        return $this->cloudflare->deliveryUrl($filePath);
    }

    /**
     * @return array{path: string, url: string}
     */
    private function storeLocally(string $jpegContents, int $userId): array
    {
        $filename = 'photos/' . $userId . '/' . uniqid('photo_', true) . '.jpg';
        Storage::disk('public')->put($filename, $jpegContents);

        return [
            'path' => $filename,
            'url'  => Storage::disk('public')->url($filename),
        ];
    }

    /**
     * @return array{path: string, url: string}
     */
    private function storeOnCloudflare(string $jpegContents, int $userId): array
    {
        $imageId  = 'photos/' . $userId . '/' . uniqid('photo_', true);
        $tempPath = tempnam(sys_get_temp_dir(), 'photo_');

        try {
            file_put_contents($tempPath, $jpegContents);

            $uploadedFile = new UploadedFile($tempPath, $imageId . '.jpg', 'image/jpeg', null, true);
            $result       = $this->cloudflare->upload($uploadedFile, $imageId);

            if (! $result['success']) {
                throw new \RuntimeException($result['error'] ?? 'Cloudflare upload failed.');
            }

            return [
                'path' => $imageId,
                'url'  => $result['delivery_url'],
            ];
        } finally {
            @unlink($tempPath);
        }
    }
}
