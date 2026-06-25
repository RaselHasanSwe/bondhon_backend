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
            'site_logo'          => null,
            'site_favicon'       => null,
            'currency'           => 'BDT',
            'currency_symbol'    => '৳',
            'contact_email'      => 'support@Enorsia.com',
            'contact_phone'      => '+880 1700-000000',
            'contact_address'    => 'Dhaka, Bangladesh',
            'facebook_url'       => 'https://facebook.com/Enorsia',
            'twitter_url'        => null,
            'instagram_url'      => null,
            'meta_title'         => 'Enorsia — Premium Matrimony Platform',
            'meta_description'   => 'Find your perfect life partner on Enorsia — Bangladesh\'s most trusted premium matrimony platform.',
            'meta_keywords'      => 'matrimony, marriage, bride, groom, matchmaking, Bangladesh',
            'face_scan_enabled'  => true,
            'email_verification_enabled' => true,
        ];

        foreach ($defaults as $key => $value) {
            SiteSetting::firstOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}

