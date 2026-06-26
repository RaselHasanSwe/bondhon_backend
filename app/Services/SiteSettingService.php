<?php

namespace App\Services;

use App\Models\SiteSetting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class SiteSettingService
{
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
            'facebook_url'     => ['type' => 'url',   'label' => 'Facebook URL'],
            'twitter_url'      => ['type' => 'url',   'label' => 'Twitter / X URL'],
            'instagram_url'    => ['type' => 'url',   'label' => 'Instagram URL'],
            'meta_title'       => ['type' => 'text',  'label' => 'Default Meta Title'],
            'meta_description' => ['type' => 'text',  'label' => 'Default Meta Description'],
            'meta_keywords'    => ['type' => 'text',  'label' => 'Default Meta Keywords'],
        ];
    }

    /**
     * Return all settings as a key → value map.
     *
     * @return array<string, string|null>
     */
    public function all(): array
    {
        return SiteSetting::allAsMap();
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

        Log::info('[SITE SETTINGS - Update] Settings updated.');
    }

    /**
     * Upload a logo or favicon image.
     * Stores in storage/app/public/settings/ and returns the public URL.
     */
    public function uploadImage(UploadedFile $file, string $key): string
    {
        // Delete old image if it exists
        $oldPath = SiteSetting::getValue($key);
        if ($oldPath) {
            $relative = str_replace(Storage::disk('public')->url(''), '', $oldPath);
            if (Storage::disk('public')->exists($relative)) {
                Storage::disk('public')->delete($relative);
            }
        }

        $path = $file->store('settings', 'public');
        $url  = Storage::disk('public')->url($path);

        SiteSetting::setValue($key, $url);

        Log::info('[SITE SETTINGS - UploadImage] Key: ' . $key . ' stored at: ' . $path);

        return $url;
    }
}

