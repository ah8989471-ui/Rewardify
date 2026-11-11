<?php

namespace Tests\Feature;

use App\Models\AdView;
use App\Models\Setting;
use App\Models\Task;
use App\Models\User;
use App\Models\Withdrawal;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class WatchEarnTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        (new SettingsSeeder)->run();
        Setting::set('postback_secret', 'test-secret-123456');
        Setting::set('admin_whatsapp', '923001234567');
        Mail::fake();
    }

    private function makeReferrer(): User
    {
        return User::factory()->create(['referral_code' => 'REFER123']);
    }

    private function registerPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'New Worker',
            'email' => 'worker@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'referral_code' => 'REFER123',
        ], $overrides);
    }

    private function verifiedUser(array $attrs = []): User
    {
        return User::factory()->create($attrs);
    }

    private function makeTask(array $attrs = []): Task
    {
        return Task::create(array_merge([
            'title' => 'Test Ad',
            'url' => 'https://example.com/ad',
            'duration_seconds' => 5,
            'reward' => '0.0500',
            'is_active' => true,
        ], $attrs));
    }

    /* ---------------------------------------------------------------
     | Mandatory referral registration
     * -------------------------------------------------------------- */

    public function test_registration_fails_without_referral_code(): void
    {
        $this->makeReferrer();

        $this->post('/register', $this->registerPayload(['referral_code' => '']))
            ->assertSessionHasErrors('referral_code');

        $this->assertDatabaseMissing('users', ['email' => 'worker@example.com']);
    }

    public function test_registration_fails_with_invalid_referral_code(): void
    {
        $this->makeReferrer();

        $this->post('/register', $this->registerPayload(['referral_code' => 'NOPE9999']))
            ->assertSessionHasErrors('referral_code');

        $this->assertDatabaseMissing('users', ['email' => 'worker@example.com']);
    }

    public function test_registration_with_valid_code_creates_unverified_user_with_own_code(): void
    {
        $referrer = $this->makeReferrer();

        $this->post('/register', $this->registerPayload())
            ->assertRedirect(route('verification.notice'));

        $user = User::where('email', 'worker@example.com')->firstOrFail();
        $this->assertNull($user->email_verified_at);
        $this->assertNotEmpty($user->referral_code);
        $this->assertNotSame('REFER123', $user->referral_code);
        $this->assertSame($referrer->id, $user->referred_by);
        $this->assertNotNull($user->verification_code);
    }

    public function test_register_page_shows_dynamic_whatsapp_button(): void
    {
        $this->get('/register')
            ->assertOk()
            ->assertSee('https://wa.me/923001234567', false)
            ->assertSee("Don't have a referral code? Get it via WhatsApp", false);

        Setting::set('admin_whatsapp', '14155552671');

        $this->get('/register')
            ->assertOk()
            ->assertSee('https://wa.me/14155552671', false);
    }

    /* ---------------------------------------------------------------
     | Email verification (6-digit code)
     * -------------------------------------------------------------- */

    public function test_user_can_verify_email_with_code_and_then_login(): void
    {
        $this->makeReferrer();
        $this->post('/register', $this->registerPayload());

        $user = User::where('email', 'worker@example.com')->firstOrFail();
        $user->forceFill([
            'verification_code' => Hash::make('123456'),
            'verification_code_expires_at' => now()->addMinutes(15),
        ])->save();

        // Wrong code is rejected
        $this->post('/verify-email', ['email' => 'worker@example.com', 'code' => '000000'])
            ->assertSessionHasErrors('code');
        $this->assertNull($user->fresh()->email_verified_at);

        // Correct code verifies
        $this->post('/verify-email', ['email' => 'worker@example.com', 'code' => '123456'])
            ->assertRedirect(route('login'));
        $this->assertNotNull($user->fresh()->email_verified_at);

        // And login now reaches the dashboard
        $this->post('/login', ['email' => 'worker@example.com', 'password' => 'password123'])
            ->assertRedirect(route('dashboard'));
    }

    public function test_unverified_user_is_redirected_to_verification_on_login(): void
    {
        $this->makeReferrer();
        $this->post('/register', $this->registerPayload());

        $this->post('/login', ['email' => 'worker@example.com', 'password' => 'password123'])
            ->assertRedirect(route('verification.notice'));
    }

    public function test_unverified_user_cannot_open_dashboard(): void
    {
        $user = User::factory()->unverified()->create();
        $this->actingAs($user)->get('/dashboard')->assertRedirect(route('verification.notice'));
    }

    /* ---------------------------------------------------------------
     | Timed ad player
     * -------------------------------------------------------------- */

    public function test_ad_completion_credits_reward_once_after_timer(): void
    {
        $user = $this->verifiedUser();
        $task = $this->makeTask();

        $this->actingAs($user)->get(route('tasks.watch', $task))->assertOk();
        $view = AdView::where('user_id', $user->id)->firstOrFail();

        // Too early: server refuses
        $this->actingAs($user)->postJson(route('tasks.complete', $task), ['token' => $view->token])
            ->assertStatus(422);
        $this->assertSame('0.0000', (string) $user->fresh()->balance);

        // After the duration has elapsed on the server clock: credited
        $this->travel(6)->seconds();
        $this->actingAs($user)->postJson(route('tasks.complete', $task), ['token' => $view->token])
            ->assertOk()->assertJson(['ok' => true]);
        $this->assertSame('0.0500', (string) $user->fresh()->balance);
        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id, 'kind' => 'ad_view', 'type' => 'credit',
        ]);

        // Replay with the same token: refused, no double credit
        $this->actingAs($user)->postJson(route('tasks.complete', $task), ['token' => $view->token])
            ->assertStatus(422);
        $this->assertSame('0.0500', (string) $user->fresh()->balance);
    }

    public function test_referral_commission_paid_on_ad_view(): void
    {
        $referrer = $this->makeReferrer();
        $referee = $this->verifiedUser(['referred_by' => $referrer->id]);
        $task = $this->makeTask(); // reward 0.05, commission 10% = 0.005

        $this->actingAs($referee)->get(route('tasks.watch', $task));
        $view = AdView::where('user_id', $referee->id)->firstOrFail();
        $this->travel(6)->seconds();
        $this->actingAs($referee)->postJson(route('tasks.complete', $task), ['token' => $view->token])->assertOk();

        $this->assertSame('0.0050', (string) $referrer->fresh()->balance);
        $this->assertDatabaseHas('transactions', [
            'user_id' => $referrer->id, 'kind' => 'referral_commission', 'type' => 'credit',
        ]);
    }

    public function test_ad_locks_for_exactly_24_hours_after_completion(): void
    {
        $user = $this->verifiedUser();
        $task = $this->makeTask();

        $this->actingAs($user)->get(route('tasks.watch', $task));
        $view = AdView::where('user_id', $user->id)->firstOrFail();
        $this->travel(6)->seconds();
        $this->actingAs($user)->postJson(route('tasks.complete', $task), ['token' => $view->token])->assertOk();

        // Immediately after completion: locked (rolling 24h, not a
        // calendar-day reset)
        $this->actingAs($user)->get(route('tasks.watch', $task))
            ->assertRedirect(route('tasks'))
            ->assertSessionHasErrors('task');

        // 23 hours later: still locked
        $this->travel(23)->hours();
        $this->actingAs($user)->get(route('tasks.watch', $task))
            ->assertRedirect(route('tasks'))
            ->assertSessionHasErrors('task');

        // Just past 24 hours from completion: watchable again
        $this->travel(61)->minutes();
        $this->actingAs($user)->get(route('tasks.watch', $task))->assertOk();
    }

    public function test_completed_ad_moves_to_end_of_list_with_cooldown(): void
    {
        $user = $this->verifiedUser();
        $first = $this->makeTask(['title' => 'First Ad', 'sort_order' => 1]);
        $second = $this->makeTask(['title' => 'Second Ad', 'sort_order' => 2]);

        // Complete the FIRST ad
        $this->actingAs($user)->get(route('tasks.watch', $first));
        $view = AdView::where('user_id', $user->id)->where('task_id', $first->id)->firstOrFail();
        $this->travel(6)->seconds();
        $this->actingAs($user)->postJson(route('tasks.complete', $first), ['token' => $view->token])->assertOk();

        $response = $this->actingAs($user)->get('/tasks')->assertOk();
        $tasks = $response->viewData('tasks');

        // Available ad first, cooling-down ad moved to the end
        $this->assertSame($second->id, $tasks[0]->id);
        $this->assertTrue($tasks[0]->is_available);
        $this->assertSame($first->id, $tasks[1]->id);
        $this->assertFalse($tasks[1]->is_available);
        $this->assertNotNull($tasks[1]->cooldown_until);

        // Another user is unaffected: both ads available, admin order kept
        $other = $this->verifiedUser();
        $tasks = $this->actingAs($other)->get('/tasks')->viewData('tasks');
        $this->assertSame($first->id, $tasks[0]->id);
        $this->assertTrue($tasks[0]->is_available && $tasks[1]->is_available);
    }

    /* ---------------------------------------------------------------
     | Withdrawals
     * -------------------------------------------------------------- */

    public function test_withdrawal_below_minimum_is_rejected(): void
    {
        $user = $this->verifiedUser();
        $user->credit('50.0000', 'bonus', 'seed');

        $this->actingAs($user)->post(route('withdraw.store'), [
            'method' => 'paypal', 'account_details' => 'me@paypal.com', 'amount' => '5.00',
        ])->assertSessionHasErrors('amount');

        $this->assertSame('50.0000', (string) $user->fresh()->balance);
        $this->assertDatabaseCount('withdrawals', 0);
    }

    public function test_withdrawal_holds_funds_and_admin_rejection_refunds(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = $this->verifiedUser();
        $user->credit('50.0000', 'bonus', 'seed');

        $this->actingAs($user)->post(route('withdraw.store'), [
            'method' => 'usdt', 'account_details' => 'TXyzWallet', 'amount' => '20.00',
        ])->assertRedirect(route('withdraw.index'));

        $this->assertSame('30.0000', (string) $user->fresh()->balance);
        $withdrawal = Withdrawal::firstOrFail();
        $this->assertSame('pending', $withdrawal->status);

        $this->actingAs($admin)->post(route('admin.withdrawals.reject', $withdrawal), ['admin_note' => 'Bad wallet'])
            ->assertSessionHas('status');
        $this->assertSame('rejected', $withdrawal->fresh()->status);
        $this->assertSame('50.0000', (string) $user->fresh()->balance);
        $this->assertDatabaseHas('transactions', ['user_id' => $user->id, 'kind' => 'withdrawal_refund']);
    }

    public function test_admin_approval_marks_withdrawal_paid_out(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = $this->verifiedUser();
        $user->credit('50.0000', 'bonus', 'seed');

        $this->actingAs($user)->post(route('withdraw.store'), [
            'method' => 'paypal', 'account_details' => 'me@paypal.com', 'amount' => '15.00',
        ]);
        $withdrawal = Withdrawal::firstOrFail();

        $this->actingAs($admin)->post(route('admin.withdrawals.approve', $withdrawal))
            ->assertSessionHas('status');
        $this->assertSame('approved', $withdrawal->fresh()->status);
        $this->assertSame('35.0000', (string) $user->fresh()->balance);
    }

    /* ---------------------------------------------------------------
     | CPAGrip postback
     * -------------------------------------------------------------- */

    public function test_postback_rejects_wrong_secret(): void
    {
        $user = $this->verifiedUser();

        $this->get('/postback/cpagrip?key=wrong&user_id='.$user->id.'&payout=1.50&txn_id=T1')
            ->assertStatus(403);
        $this->assertSame('0.0000', (string) $user->fresh()->balance);
    }

    public function test_postback_credits_once_and_dedupes(): void
    {
        $referrer = $this->makeReferrer();
        $user = $this->verifiedUser(['referred_by' => $referrer->id]);

        $url = '/postback/cpagrip?key=test-secret-123456&user_id='.$user->id.'&payout=1.50&txn_id=TXN-100&offer_id=OFF1';
        $this->get($url)->assertOk()->assertSee('OK');
        $this->assertSame('1.5000', (string) $user->fresh()->balance);
        // 10% referral commission on offerwall earnings too
        $this->assertSame('0.1500', (string) $referrer->fresh()->balance);

        $this->get($url)->assertOk()->assertSee('duplicate');
        $this->assertSame('1.5000', (string) $user->fresh()->balance);
        $this->assertDatabaseHas('postback_logs', ['txn_id' => 'TXN-100', 'status' => 'credited']);
    }

    /* ---------------------------------------------------------------
     | Admin: settings, users, access control
     * -------------------------------------------------------------- */

    public function test_non_admin_cannot_access_admin_panel(): void
    {
        $user = $this->verifiedUser();
        $this->actingAs($user)->get('/admin')->assertStatus(403);
    }

    public function test_admin_can_update_global_settings_including_whatsapp(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post(route('admin.settings.update'), [
            'site_name' => 'MyEarnSite',
            'site_tagline' => 'Earn daily',
            'admin_whatsapp' => '+92 300-9998887',
            'min_withdrawal' => '5.00',
            'default_reward' => '0.04',
            'default_duration' => '15',
            'referral_commission_percent' => '12',
        ])->assertSessionHas('status');

        $this->assertSame('923009998887', Setting::get('admin_whatsapp'));
        $this->assertSame('MyEarnSite', Setting::get('site_name'));

        // The register page is guest-only, so log the admin out first.
        $this->post(route('logout'));
        $this->get('/register')->assertSee('https://wa.me/923009998887', false);
    }

    public function test_admin_can_adjust_balance_and_suspend_user(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = $this->verifiedUser();

        $this->actingAs($admin)->post(route('admin.users.adjust', $user), ['amount' => '7.50', 'note' => 'Goodwill'])
            ->assertSessionHas('status');
        $this->assertSame('7.5000', (string) $user->fresh()->balance);

        $this->actingAs($admin)->post(route('admin.users.adjust', $user), ['amount' => '-2.50'])
            ->assertSessionHas('status');
        $this->assertSame('5.0000', (string) $user->fresh()->balance);

        // Debit beyond balance is refused
        $this->actingAs($admin)->post(route('admin.users.adjust', $user), ['amount' => '-99.00'])
            ->assertSessionHasErrors('amount');
        $this->assertSame('5.0000', (string) $user->fresh()->balance);

        // Suspension blocks the panel (fresh() reloads the user the way
        // a real new request would - the in-memory copy is stale)
        $this->actingAs($admin)->post(route('admin.users.toggle', $user))->assertSessionHas('status');
        $this->assertSame('suspended', $user->fresh()->status);
        $this->actingAs($user->fresh())->get('/dashboard')->assertRedirect(route('login'));
    }

    public function test_player_opens_ad_in_new_tab_with_early_return_warning(): void
    {
        $user = User::factory()->create();
        $task = $this->makeTask();

        $this->actingAs($user)->get(route('tasks.watch', $task))
            ->assertOk()
            ->assertSee('window.open', false)
            ->assertSee('id="warnBox"', false)
            ->assertSee('you came back before the ad finished', false)
            ->assertDontSee('id="adFrame"', false);
    }

    public function test_a_ads_embed_code_tasks_open_embed_page_in_new_tab(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $code = '<iframe data-aa="123456" src="https://ad.a-ads.com/123456"></iframe>';

        // Admin can create a task with an embed code and no URL
        $this->actingAs($admin)->post(route('admin.tasks.store'), [
            'title' => 'A-Ads banner',
            'embed_code' => $code,
            'duration_seconds' => 20,
            'reward' => '0.0100',
            'is_active' => '1',
        ])->assertRedirect(route('admin.tasks'));
        $task = Task::where('title', 'A-Ads banner')->firstOrFail();
        $this->assertSame($code, $task->embed_code);

        // The player points the new tab at our embed page for this view
        $user = User::factory()->create();
        $html = $this->actingAs($user)->get(route('tasks.watch', $task))->assertOk()->getContent();
        $view = $user->adViews()->where('task_id', $task->id)->firstOrFail();
        $this->assertStringContainsString(route('tasks.embed', $view->token), $html);

        // The embed page renders the code for the owner only
        $this->actingAs($user)->get(route('tasks.embed', $view->token))
            ->assertOk()->assertSee($code, false);
        $stranger = User::factory()->create();
        $this->actingAs($stranger)->get(route('tasks.embed', $view->token))->assertNotFound();
    }

    public function test_admin_can_change_own_password_from_settings(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'email' => 'boss@example.com']);

        // Wrong current password is rejected
        $this->actingAs($admin)->post(route('admin.settings.password'), [
            'current_password' => 'not-the-password',
            'password' => 'new-secret-99',
            'password_confirmation' => 'new-secret-99',
        ])->assertSessionHasErrors('current_password');

        // Correct current password changes it
        $this->actingAs($admin)->post(route('admin.settings.password'), [
            'current_password' => 'password',
            'password' => 'new-secret-99',
            'password_confirmation' => 'new-secret-99',
        ])->assertSessionHas('status');

        $this->post(route('logout'));
        $this->post('/login', ['email' => 'boss@example.com', 'password' => 'password'])
            ->assertSessionHasErrors('email');
        $this->post('/login', ['email' => 'boss@example.com', 'password' => 'new-secret-99'])
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_admin_can_manage_tasks(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post(route('admin.tasks.store'), [
            'title' => 'Sponsored Video',
            'url' => 'https://example.com/video',
            'duration_seconds' => 20,
            'reward' => '0.07',
            'is_active' => '1',
        ])->assertRedirect(route('admin.tasks'));

        $task = Task::where('title', 'Sponsored Video')->firstOrFail();
        $this->assertSame('0.0700', (string) $task->reward);

        $this->actingAs($admin)->delete(route('admin.tasks.destroy', $task))
            ->assertSessionHas('status');
        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }

    /* ---------------------------------------------------------------
     | Offerwall display
     * -------------------------------------------------------------- */

    public function test_offerwall_embeds_url_with_user_id_substituted(): void
    {
        Setting::set('cpagrip_offerwall_url', 'https://wall.example.com/?subid={USER_ID}');
        $user = $this->verifiedUser();

        $this->actingAs($user)->get('/offerwall')
            ->assertOk()
            ->assertSee('https://wall.example.com/?subid='.$user->id, false);
    }

    public function test_dashboard_shows_balance_and_referral_code(): void
    {
        $user = $this->verifiedUser();
        $user->credit('3.2500', 'bonus', 'Welcome');

        $this->actingAs($user)->get('/dashboard')
            ->assertOk()
            ->assertSee('$3.25')
            ->assertSee($user->referral_code);
    }
}
