<?php

namespace Database\Seeders;

use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersSeeder extends Seeder
{
    public function run(): void
    {
        // Root admin. Its referral code (ADMIN001) is the bootstrap code
        // the admin can share via WhatsApp with the very first users.
        // Email/password come from the environment so production can use
        // a real, secret password (ADMIN_EMAIL / ADMIN_PASSWORD in .env)
        // instead of the local default. NOTE: firstOrCreate only applies
        // these when the admin is first created - to change the password
        // later, see docs/DEPLOY_VERCEL_SUPABASE.md.
        $admin = User::firstOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@watchearn.local')],
            [
                'name' => 'Administrator',
                'password' => Hash::make(env('ADMIN_PASSWORD', 'password')),
                'role' => 'admin',
                'status' => 'active',
                'referral_code' => 'ADMIN001',
                'email_verified_at' => now(),
            ]
        );

        // Demo worker account - local testing only, never seeded into a
        // production database.
        if (! app()->environment('production')) {
            $demo = User::firstOrCreate(
                ['email' => 'demo@watchearn.local'],
                [
                    'name' => 'Demo Worker',
                    'password' => Hash::make('password'),
                    'role' => 'user',
                    'status' => 'active',
                    'referral_code' => 'DEMO2026',
                    'referred_by' => $admin->id,
                    'email_verified_at' => now(),
                ]
            );
            $demo->forceFill(['balance' => '2.5000'])->save();
        }

        // A few sample tasks so the worker dashboard is not empty.
        $samples = [
            ['title' => 'Watch: Product Promo (Sample)', 'url' => 'https://example.com/promo', 'duration_seconds' => 15, 'reward' => '0.0300', 'sort_order' => 1],
            ['title' => 'Watch: App Trailer (Sample)', 'url' => 'https://example.com/trailer', 'duration_seconds' => 20, 'reward' => '0.0500', 'sort_order' => 2],
            ['title' => 'Visit: Sponsor Page (Sample)', 'url' => 'https://example.com/sponsor', 'duration_seconds' => 20, 'reward' => '0.0400', 'sort_order' => 3],
        ];
        foreach ($samples as $sample) {
            Task::firstOrCreate(['title' => $sample['title']], $sample + ['is_active' => true]);
        }
    }
}
