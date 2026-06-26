<?php

namespace App\Services;

use App\Models\SiteSetting;
use App\Services\EmailVerificationOtpService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SiteSettingService
{
    public const CACHE_KEY = 'site_settings:all';

    public const CACHE_TTL = 3600;

    public function __construct(
        private readonly CloudflareImageService $cloudflare,
    ) {}

    /**
     * All defined setting keys and their types/categories.
     *
     * @return array<string, array{type: string, label: string}>
     */
    public static function definitions(): array
    {
        return [
            'site_name'        => ['type' => 'text',  'label' => 'Site Name'],
            'site_slogan'      => ['type' => 'text',  'label' => 'Site Slogan'],
            'site_logo'        => ['type' => 'image', 'label' => 'Site Logo'],
            'site_favicon'     => ['type' => 'image', 'label' => 'Site Favicon'],
            'currency'         => ['type' => 'text',  'label' => 'Currency Code (e.g. BDT)'],
            'currency_symbol'  => ['type' => 'text',  'label' => 'Currency Symbol (e.g. ৳)'],
            'contact_email'    => ['type' => 'email', 'label' => 'Contact Email'],
            'contact_phone'    => ['type' => 'text',  'label' => 'Contact Phone'],
            'contact_address'  => ['type' => 'text',  'label' => 'Contact Address'],
            'face_scan_enabled' => ['type' => 'boolean', 'label' => 'Enable Face Scan Verification'],
            'email_verification_enabled' => ['type' => 'boolean', 'label' => 'Enable Email Verification'],
            'photo_auto_approval_enabled' => ['type' => 'boolean', 'label' => 'Photo Auto Approval'],
            'facebook_url'     => ['type' => 'url',   'label' => 'Facebook URL'],
            'twitter_url'      => ['type' => 'url',   'label' => 'Twitter / X URL'],
            'instagram_url'    => ['type' => 'url',   'label' => 'Instagram URL'],
            'linkedin_url'     => ['type' => 'url',   'label' => 'LinkedIn URL'],
            'meta_title'       => ['type' => 'text',  'label' => 'Default Meta Title'],
            'meta_description' => ['type' => 'text',  'label' => 'Default Meta Description'],
            'meta_keywords'    => ['type' => 'text',  'label' => 'Default Meta Keywords'],
        ];
    }

    /**
     * Return all settings as a key → value map (cached).
     *
     * @return array<string, string|null>
     */
    public function all(): array
    {
        $settings = Cache::remember(self::CACHE_KEY, self::CACHE_TTL, fn () => SiteSetting::allAsMap());

        $settings['email_otp_expiry_minutes'] = (string) EmailVerificationOtpService::EXPIRY_MINUTES;

        return $settings;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->all()[$key] ?? $default;
    }

    public function boolean(string $key, bool $default = false): bool
    {
        $value = $this->get($key);

        if ($value === null) {
            return $default;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    public function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * @return array<string, array{label: string, url: string}>
     */
    public function socialLinks(): array
    {
        $links = [];

        foreach ([
            'facebook_url'  => 'Facebook',
            'twitter_url'   => 'Twitter',
            'instagram_url' => 'Instagram',
            'linkedin_url'  => 'LinkedIn',
        ] as $key => $label) {
            $url = $this->get($key);
            if (! empty($url)) {
                $links[$key] = ['label' => $label, 'url' => $url];
            }
        }

        return $links;
    }

    /**
     * Mass-upsert text settings.
     *
     * @param array<string, string|null> $data
     */
    public function update(array $data): void
    {
        foreach ($data as $key => $value) {
            SiteSetting::setValue($key, $value);
        }

        $this->forgetCache();

        Log::info('[SITE SETTINGS - Update] Settings updated.');
    }

    /**
     * Upload a logo or favicon to Cloudflare and persist the image ID.
     *
     * @return array{success: bool, error: string|null}
     */
    public function updateImage(string $key, UploadedFile $file): array
    {
        $oldCfId = $this->get($key);
        if ($oldCfId) {
            $this->cloudflare->delete($oldCfId);
        }

        $imageId = $key . '/' . time() . '.' . $file->getClientOriginalExtension();
        $result = $this->cloudflare->upload($file, $imageId);

        if (! $result['success']) {
            return [
                'success' => false,
                'error'   => $result['error'] ?? 'Image upload failed.',
            ];
        }

        SiteSetting::setValue($key, $imageId);
        $this->forgetCache();

        Log::info('[SITE SETTINGS - UpdateImage] Key: ' . $key . ' stored as: ' . $imageId);

        return ['success' => true, 'error' => null];
    }
}
