<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_pages_render(): void
    {
        (new SettingsSeeder)->run();
        Mail::fake();

        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create();
        $task = Task::create([
            'title' => 'Smoke Ad', 'url' => 'https://example.com',
            'duration_seconds' => 15, 'reward' => '0.0500', 'is_active' => true,
        ]);

        foreach (['/', '/register', '/login', '/verify-email'] as $url) {
            $this->get($url)->assertOk();
        }

        foreach (['/dashboard', '/tasks', '/offerwall', '/wallet', '/referrals', '/withdraw',
            route('tasks.watch', $task)] as $url) {
            $this->actingAs($user)->get($url)->assertOk();
        }

        foreach (['/admin', '/admin/users', '/admin/users/'.$user->id, '/admin/settings',
            '/admin/cpagrip', '/admin/tasks', '/admin/tasks/create',
            '/admin/tasks/'.$task->id.'/edit', '/admin/withdrawals'] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
    }

    public function test_mobile_sidebar_is_not_permanently_hidden(): void
    {
        (new SettingsSeeder)->run();
        $user = User::factory()->create();

        // Regression: the sidebar used to carry a permanent 'hidden'
        // class that always beat the 'block' class added by the toggle
        // script, so the hamburger menu could never open it on mobile.
        $this->actingAs($user)->get('/dashboard')
            ->assertOk()
            ->assertSee('id="sidebarToggle"', false)
            ->assertSee('id="sidebar"', false)
            ->assertDontSee('z-40 hidden', false);
    }
}
