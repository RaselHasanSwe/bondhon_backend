<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CloudflareImageService
{
    private string $accountId;
    private string $accountHash;
    private string $apiToken;
    private string $deliveryUrl;
    private string $baseUrl;

    public function __construct()
    {
        $this->accountId  = config('cloudflare.account_id');
        $this->apiToken   = config('cloudflare.api_token');
        $this->accountHash = config('cloudflare.account_hash');
        $this->deliveryUrl = rtrim(config('cloudflare.image_delivery_url'), '/');
        $this->baseUrl    = "https://api.cloudflare.com/client/v4/accounts/{$this->accountId}/images/v1";
    }

    /**
     * Upload an image to Cloudflare Images.
     *
     * @param  UploadedFile|string  $image   UploadedFile instance OR a public URL string
     * @param  string               $imageId  Custom ID used as the Cloudflare image ID
     * @param  array                $metadata Optional key-value metadata (max 1024 bytes total)
     * @return array{success: bool, image_id: string|null, delivery_url: string|null, error: string|null}
     */
    public function upload(UploadedFile|string $image, string $imageId, array $metadata = []): array
    {
        try {
            $request = Http::withToken($this->apiToken)
                ->timeout(30)
                ->asMultipart();

            // Attach the file or URL
            if ($image instanceof UploadedFile) {
                $request = $request->attach(
                    'file',
                    file_get_contents($image->getRealPath()),
                    $image->getClientOriginalName()
                );
            } else {
                // $image is a URL — Cloudflare will fetch it
                $request = $request->attach('url', $image, 'url');
            }

            $response = $request->post($this->baseUrl, array_filter([
                'id'       => $imageId,
                'metadata' => $metadata ? json_encode($metadata) : null,
            ]));

            $body = $response->json();

            if (! $response->successful() || ! ($body['success'] ?? false)) {
                $errors = $body['errors'] ?? [['message' => 'Unknown Cloudflare error']];
                $errorMsg = collect($errors)->pluck('message')->implode(', ');

                Log::error('CloudflareImageService::upload failed', [
                    'image_id'   => $imageId,
                    'status'     => $response->status(),
                    'errors'     => $errors,
                    'cf_response' => $body,
                ]);

                return [
                    'success'      => false,
                    'image_id'     => null,
                    'delivery_url' => null,
                    'error'        => $errorMsg,
                ];
            }

            $cfImageId   = $body['result']['id'];
            $deliveryUrl = "{$this->deliveryUrl}/{$this->accountHash}/{$cfImageId}/public";

            Log::info('CloudflareImageService::upload success', [
                'image_id'     => $cfImageId,
                'delivery_url' => $deliveryUrl,
            ]);

            return [
                'success'      => true,
                'image_id'     => $cfImageId,
                'delivery_url' => $deliveryUrl,
                'error'        => null,
            ];

        } catch (\Throwable $e) {
            Log::error('CloudflareImageService::upload exception', [
                'image_id'  => $imageId,
                'exception' => $e->getMessage(),
                'trace'     => $e->getTraceAsString(),
            ]);

            return [
                'success'      => false,
                'image_id'     => null,
                'delivery_url' => null,
                'error'        => $e->getMessage(),
            ];
        }
    }

    /**
     * Delete an image from Cloudflare Images by its ID.
     *
     * @param  string  $imageId  The Cloudflare image ID (same as the custom ID you passed on upload)
     * @return array{success: bool, error: string|null}
     */
    public function delete(string $imageId): array
    {
        try {
            $response = Http::withToken($this->apiToken)
                ->timeout(15)
                ->delete("{$this->baseUrl}/{$imageId}");

            $body = $response->json();

            if (! $response->successful() || ! ($body['success'] ?? false)) {
                $errors   = $body['errors'] ?? [['message' => 'Unknown Cloudflare error']];
                $errorMsg = collect($errors)->pluck('message')->implode(', ');

                Log::error('CloudflareImageService::delete failed', [
                    'image_id'    => $imageId,
                    'status'      => $response->status(),
                    'errors'      => $errors,
                    'cf_response' => $body,
                ]);

                return [
                    'success' => false,
                    'error'   => $errorMsg,
                ];
            }

            Log::info('CloudflareImageService::delete success', [
                'image_id' => $imageId,
            ]);

            return [
                'success' => true,
                'error'   => null,
            ];

        } catch (\Throwable $e) {
            Log::error('CloudflareImageService::delete exception', [
                'image_id'  => $imageId,
                'exception' => $e->getMessage(),
                'trace'     => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error'   => $e->getMessage(),
            ];
        }
    }

    /**
     * Build a delivery URL for a given variant (default: "public").
     * Use this when you already have the image ID stored and just need the URL.
     */
    public function deliveryUrl(string $imageId, string $variant = 'public'): string
    {
        return "{$this->deliveryUrl}/{$this->accountHash}/{$imageId}/{$variant}";
    }
}
