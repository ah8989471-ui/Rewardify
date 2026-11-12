<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BitcoTasksTest extends TestCase
{
    use RefreshDatabase;

    protected string $secret = 'bt-test-secret-123456';

    protected function setUp(): void
    {
        parent::setUp();
        (new SettingsSeeder)->run();
        Setting::set('bitcotasks_secret', $this->secret);
    }

    private function sig(string $subId, string $transId, string $reward): string
    {
        return md5($subId.$transId.$reward.$this->secret);
    }

    /** Valid postback payload; signature is recomputed after overrides. */
    private function payload(array $overrides = []): array
    {
        $data = array_merge([
            'subId' => '1',
            'transId' => 'BT-100',
            'offer_name' => 'Test PTC Ad',
            'offer_type' => 'ptc',
            'reward' => '1.25',
            'reward_value' => '1',
            'payout' => '0.50',
            'userIp' => '127.0.0.1',
            'country' => 'US',
            'status' => '1',
            'debug' => '0',
        ], $overrides);

        $data['signature'] = $this->sig((string) $data['subId'], (string) $data['transId'], (string) $data['reward']);

        return $data;
    }

    private function verifiedUser(array $attrs = []): User
    {
        return User::factory()->create($attrs);
    }

    /* ---------------------------------------------------------------
     | Credit path
     * -------------------------------------------------------------- */

    public function test_valid_signature_credits_user_and_pays_referral_commission(): void
    {
        $referrer = User::factory()->create(['referral_code' => 'REFER123']);
        $user = $this->verifiedUser(['referred_by' => $referrer->id]);

        $this->post('/postback/bitcotasks', $this->payload(['subId' => (string) $user->id]))
            ->assertOk()->assertSee('ok');

        // reward is USD already (currency USD, exchange rate 1)
        $this->assertSame('1.2500', (string) $user->fresh()->balance);
        // 10% referral commission on BitcoTasks earnings too
        $this->assertSame('0.1250', (string) $referrer->fresh()->balance);
        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id, 'kind' => 'offerwall', 'type' => 'credit', 'amount' => '1.2500',
        ]);
        $this->assertDatabaseHas('postback_logs', ['txn_id' => 'bitcotasks:BT-100', 'status' => 'credited']);
    }

    public function test_get_request_is_accepted_like_cpagrip(): void
    {
        $user = $this->verifiedUser();
        $data = $this->payload(['subId' => (string) $user->id]);

        $this->get('/postback/bitcotasks?'.http_build_query($data))
            ->assertOk()->assertSee('ok');
        $this->assertSame('1.2500', (string) $user->fresh()->balance);
    }

    public function test_invalid_signature_rejected_and_nothing_credited(): void
    {
        $user = $this->verifiedUser();
        $data = $this->payload(['subId' => (string) $user->id]);
        $data['signature'] = 'deadbeefdeadbeefdeadbeefdeadbeef';

        $this->post('/postback/bitcotasks', $data)->assertStatus(403);
        $this->assertSame('0.0000', (string) $user->fresh()->balance);
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_empty_secret_rejects_everything(): void
    {
        Setting::set('bitcotasks_secret', '');
        $user = $this->verifiedUser();

        $this->post('/postback/bitcotasks', $this->payload(['subId' => (string) $user->id]))
            ->assertStatus(403);
        $this->assertSame('0.0000', (string) $user->fresh()->balance);
    }

    public function test_duplicate_transid_does_not_double_credit(): void
    {
        $user = $this->verifiedUser();
        $data = $this->payload(['subId' => (string) $user->id]);

        $this->post('/postback/bitcotasks', $data)->assertOk()->assertSee('ok');
        $this->assertSame('1.2500', (string) $user->fresh()->balance);

        // Replay: still answers ok, balance unchanged, one credit only
        $this->post('/postback/bitcotasks', $data)->assertOk()->assertSee('ok');
        $this->assertSame('1.2500', (string) $user->fresh()->balance);
        $this->assertSame(1, $user->fresh()->transactions()->where('kind', 'offerwall')->where('type', 'credit')->count());
    }

    public function test_debug_postback_changes_nothing(): void
    {
        $user = $this->verifiedUser();

        $this->post('/postback/bitcotasks', $this->payload(['subId' => (string) $user->id, 'debug' => '1']))
            ->assertOk()->assertSee('ok');

        $this->assertSame('0.0000', (string) $user->fresh()->balance);
        $this->assertDatabaseCount('transactions', 0);
        $this->assertDatabaseHas('postback_logs', ['txn_id' => 'bitcotasks:BT-100', 'status' => 'test']);
    }

    public function test_missing_user_handled_without_500(): void
    {
        $this->post('/postback/bitcotasks', $this->payload(['subId' => '999999']))
            ->assertOk()->assertSee('ok');
        $this->assertDatabaseHas('postback_logs', ['txn_id' => 'bitcotasks:BT-100', 'status' => 'rejected']);
    }

    /* ---------------------------------------------------------------
     | Chargebacks (status=2)
     * -------------------------------------------------------------- */

    public function test_chargeback_subtracts_once_and_repeated_chargeback_does_not_subtract_twice(): void
    {
        $user = $this->verifiedUser();

        // Credit $2.00 first
        $this->post('/postback/bitcotasks', $this->payload([
            'subId' => (string) $user->id, 'transId' => 'BT-200', 'reward' => '2.00',
        ]))->assertOk();
        $this->assertSame('2.0000', (string) $user->fresh()->balance);

        // Chargeback for the same transId: balance back to zero
        $this->post('/postback/bitcotasks', $this->payload([
            'subId' => (string) $user->id, 'transId' => 'BT-200', 'reward' => '2.00', 'status' => '2',
        ]))->assertOk()->assertSee('ok');
        $this->assertSame('0.0000', (string) $user->fresh()->balance);
        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id, 'kind' => 'offerwall', 'type' => 'debit', 'amount' => '2.0000',
        ]);

        // Repeated chargeback: subtracts nothing more
        $this->post('/postback/bitcotasks', $this->payload([
            'subId' => (string) $user->id, 'transId' => 'BT-200', 'reward' => '2.00', 'status' => '2',
        ]))->assertOk()->assertSee('ok');
        $this->assertSame('0.0000', (string) $user->fresh()->balance);
        $this->assertSame(1, $user->fresh()->transactions()->where('kind', 'offerwall')->where('type', 'debit')->count());
    }

    public function test_chargeback_without_prior_credit_still_records_negative_balance(): void
    {
        $user = $this->verifiedUser();

        $this->post('/postback/bitcotasks', $this->payload([
            'subId' => (string) $user->id, 'transId' => 'BT-300', 'reward' => '0.50', 'status' => '2',
        ]))->assertOk()->assertSee('ok');

        // Nets out even though no credit ever arrived
        $this->assertSame('-0.5000', (string) $user->fresh()->balance);
        $this->assertDatabaseHas('postback_logs', ['txn_id' => 'bitcotasks:BT-300:chargeback', 'status' => 'chargeback']);
    }

    /* ---------------------------------------------------------------
     | Offerwall page + admin settings
     * -------------------------------------------------------------- */

    public function test_offerwall_page_embeds_iframe_with_api_key_and_user_id(): void
    {
        Setting::set('bitcotasks_api_key', 'APIKEY123');
        $user = $this->verifiedUser();

        $this->actingAs($user)->get(route('bitcotasks'))
            ->assertOk()
            ->assertSee('https://bitcotasks.com/offerwall/APIKEY123/'.$user->id, false);

        Setting::set('bitcotasks_api_key', '');
        $this->actingAs($user)->get(route('bitcotasks'))
            ->assertOk()
            ->assertSee('not configured yet');
    }

    public function test_admin_can_save_bitcotasks_settings_and_blank_secret_keeps_old_one(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post(route('admin.bitcotasks.update'), [
            'bitcotasks_api_key' => 'APIKEY999',
            'bitcotasks_secret' => 'new-bitcotasks-secret-123',
        ])->assertSessionHas('status');

        $this->assertSame('APIKEY999', Setting::get('bitcotasks_api_key'));
        $this->assertSame('new-bitcotasks-secret-123', Setting::get('bitcotasks_secret'));

        // Blank secret keeps the saved one; the API key still updates
        $this->actingAs($admin)->post(route('admin.bitcotasks.update'), [
            'bitcotasks_api_key' => 'APIKEY000',
            'bitcotasks_secret' => '',
        ])->assertSessionHas('status');

        $this->assertSame('APIKEY000', Setting::get('bitcotasks_api_key'));
        $this->assertSame('new-bitcotasks-secret-123', Setting::get('bitcotasks_secret'));

        // The saved secret is never rendered back on the admin page,
        // but the postback URL (built from config('app.url')) is shown
        $this->actingAs($admin)->get(route('admin.cpagrip'))
            ->assertOk()
            ->assertDontSee('new-bitcotasks-secret-123', false)
            ->assertSee(url('/postback/bitcotasks'), false);
    }

    public function test_non_admin_cannot_save_bitcotasks_settings(): void
    {
        $user = $this->verifiedUser();

        $this->actingAs($user)->post(route('admin.bitcotasks.update'), [
            'bitcotasks_api_key' => 'X',
            'bitcotasks_secret' => 'some-secret-value-123',
        ])->assertStatus(403);
        $this->assertSame('', Setting::get('bitcotasks_api_key'));
    }
}
