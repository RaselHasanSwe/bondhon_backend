<?php

namespace Database\Seeders;

use App\Models\SiteSetting;
use Illuminate\Database\Seeder;

class SiteSettingSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            'site_name'          => 'Bondhon',
            'site_logo'          => null,
            'site_favicon'       => null,
            'currency'           => 'BDT',
            'currency_symbol'    => '৳',
            'contact_email'      => 'support@bondhon.com',
            'contact_phone'      => '+880 1700-000000',
            'contact_address'    => 'Dhaka, Bangladesh',
            'facebook_url'       => 'https://facebook.com/bondhon',
            'twitter_url'        => null,
            'instagram_url'      => null,
            'meta_title'         => 'Bondhon — Premium Matrimony Platform',
            'meta_description'   => 'Find your perfect life partner on Bondhon — Bangladesh\'s most trusted premium matrimony platform.',
            'meta_keywords'      => 'matrimony, marriage, bride, groom, matchmaking, Bangladesh',
        ];

        foreach ($defaults as $key => $value) {
            SiteSetting::firstOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}

