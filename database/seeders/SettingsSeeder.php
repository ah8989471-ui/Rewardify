<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            'site_name' => 'WatchEarn',
            'site_tagline' => 'Watch. Earn. Withdraw.',
            // Admin WhatsApp number in full international format, digits
            // only (country code + number, no + or spaces). CHANGE THIS
            // in Admin > Global Settings - it powers the "Get it via
            // WhatsApp" button on the registration page.
            'admin_whatsapp' => '923000000000',
            'min_withdrawal' => '10.00',
            'default_reward' => '0.05',
            'default_duration' => '20',
            'referral_commission_percent' => '10',
            // CPAGrip: paste your offerwall URL (use {USER_ID} where the
            // user id / subid goes) OR a full iframe embed code.
            'cpagrip_offerwall_url' => '',
            'cpagrip_iframe_code' => '',
            // Shared secret required on the postback endpoint.
            'postback_secret' => Str::random(32),
            // BitcoTasks: paste the app API key + S2S secret from
            // BitcoTasks (My Apps -> Edit) in Admin > CPAGrip >
            // BitcoTasks section. Empty until the admin sets them.
            'bitcotasks_api_key' => '',
            'bitcotasks_secret' => '',
        ];

        foreach ($defaults as $key => $value) {
            Setting::firstOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
