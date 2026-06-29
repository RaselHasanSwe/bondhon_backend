<?php

namespace Database\Seeders;

use App\Models\SiteSetting;
use Illuminate\Database\Seeder;

class SiteSettingSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            'site_name'          => 'Enorsia',
            'site_slogan'        => "Bangladesh's Most Trusted Matrimony Platform",
            'site_logo'          => null,
            'site_favicon'       => null,
            'currency'           => 'BDT',
            'currency_symbol'    => '৳',
            'contact_email'      => 'info@enorsia.com',
            'contact_phone'      => '+880 1770-744894',
            'contact_address'    => 'Dhaka, Bangladesh',
            'facebook_url'       => 'https://facebook.com/Enorsia',
            'twitter_url'        => 'https://twitter.com/Enorsia',
            'instagram_url'      => 'https://instagram.com/Enorsia',
            'linkedin_url'       => 'https://linkedin.com/company/Enorsia',
            'meta_title'         => 'Enorsia — Premium Matrimony Platform',
            'meta_description'   => 'Find your perfect life partner on Enorsia — Bangladesh\'s most trusted premium matrimony platform.',
            'meta_keywords'      => 'matrimony, marriage, bride, groom, matchmaking, Bangladesh',
            'face_scan_enabled'  => false,
            'email_verification_enabled' => false,
            'photo_auto_approval_enabled' => true,
        ];

        foreach ($defaults as $key => $value) {
            SiteSetting::firstOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}

