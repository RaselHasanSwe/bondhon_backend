<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;

class FaceScanStorageService
{
    public function __construct(
        private readonly CloudflareImageService $cloudflare,
    ) {}

    /**
     * @return array{path: string}
     */
    public function store(string $jpegContents, int $userId, int $sessionId, string $captureKey): array
    {
        $imageId  = sprintf('face-scans/%d/%d/%s_%s', $userId, $sessionId, $captureKey, uniqid());
        $tempPath = tempnam(sys_get_temp_dir(), 'face_scan_');

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

    public function delete(?string $imageId): void
    {
        if (! $imageId) {
            return;
        }

        $this->cloudflare->delete($imageId);
    }
}
