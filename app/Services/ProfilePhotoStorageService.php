<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;

class ProfilePhotoStorageService
{
    public function __construct(
        private readonly CloudflareImageService $cloudflare,
    ) {}

    /**
     * @return array{path: string}
     */
    public function store(string $jpegContents, int $userId): array
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

            return ['path' => $imageId];
        } finally {
            @unlink($tempPath);
        }
    }

    public function delete(?string $filePath): void
    {
        if (! $filePath) {
            return;
        }

        $this->cloudflare->delete($filePath);
    }
}
