<?php

namespace App\Http\Controllers;

use App\Models\PostbackLog;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * CPAGrip postback / webhook endpoint.
 *
 * Configure this URL in CPAGrip as your postback URL:
 *
 *   https://your-domain.com/postback/cpagrip?key=YOUR_SECRET
 *       &user_id={subid}&payout={payout}&txn_id={transaction_id}&offer_id={offer_id}
 *
 * Security: the shared secret (`key`) must match the postback secret
 * set in Admin > CPAGrip Settings. Every transaction id is credited
 * at most once - replays are logged as duplicates, never re-paid.
 */
class PostbackController extends Controller
{
    public function cpagrip(Request $request)
    {
        $secret = (string) Setting::get('postback_secret', '');
        $key = (string) $request->input('key', '');

        if ($secret === '' || ! hash_equals($secret, $key)) {
            $this->log($request, null, 'rejected');

            return response('invalid key', 403);
        }

        $userId = $request->input('user_id', $request->input('subid'));
        $payoutRaw = $request->input('payout', $request->input('amount'));
        $txnId = $request->input('txn_id', $request->input('transaction_id'));
        $offerId = $request->input('offer_id');

        $user = $userId ? User::find($userId) : null;
        $payout = is_numeric($payoutRaw) ? number_format((float) $payoutRaw, 4, '.', '') : '0.0000';

        if (! $user || bccomp($payout, '0', 4) <= 0) {
            $this->log($request, null, 'rejected', $txnId, $offerId, $payout);

            return response('invalid user or payout', 422);
        }

        if ($txnId && PostbackLog::where('txn_id', $txnId)->where('status', 'credited')->exists()) {
            $this->log($request, $user->id, 'duplicate', $txnId, $offerId, $payout);

            return response('duplicate', 200);
        }

        $user->credit($payout, 'offerwall', 'CPAGrip offer completed'.($offerId ? " (offer {$offerId})" : ''), 'cpagrip:'.($txnId ?? uniqid()));
        $user->payReferralCommission($payout, 'an offerwall completion');

        $this->log($request, $user->id, 'credited', $txnId, $offerId, $payout);

        return response('OK', 200);
    }

    /**
     * BitcoTasks S2S postback endpoint.
     *
     * BitcoTasks sends a form-encoded POST to the Postback URL set in
     * the app's Config step (Admin > CPAGrip > BitcoTasks section):
     *
     *   https://your-domain.com/postback/bitcotasks
     *
     * Fields: subId, transId, offer_name, offer_type (ptc/offer/task/
     * shortlink), reward, reward_value, payout, userIp, country,
     * status (1 = credit, 2 = chargeback), debug, signature.
     *
     * The publisher currency is configured in BitcoTasks as USD with
     * exchange rate 1, so `reward` IS the USD amount to credit - no
     * conversion is done here.
     *
     * Security: signature = md5(subId . transId . reward . s2s_secret)
     * computed over the RAW reward string exactly as sent. The secret
     * lives in the `bitcotasks_secret` setting; an empty secret rejects
     * everything. Every transId is credited at most once and charged
     * back at most once - replays are logged as duplicates.
     *
     * BitcoTasks treats any reply other than the literal lowercase
     * string `ok` as a failure and retries, so every handled case
     * (credit, duplicate, debug/test, chargeback, unknown user)
     * answers `ok`. Only a bad/missing signature is rejected (403) -
     * that is a forged request, not a delivery to retry.
     */
    public function bitcotasks(Request $request)
    {
        $secret = (string) Setting::get('bitcotasks_secret', '');
        $subId = (string) $request->input('subId', '');
        $transId = (string) $request->input('transId', '');
        $rewardRaw = (string) $request->input('reward', '');
        $offerName = $request->input('offer_name');
        $signature = (string) $request->input('signature', '');

        // Namespaced log ids: the shared postback_logs table has a
        // unique txn_id, so BitcoTasks ids must never collide with
        // CPAGrip ids, and a chargeback for a transId must be loggable
        // alongside its credit.
        $logTxnId = $transId === '' ? null : 'bitcotasks:'.$transId;
        $logChargebackTxnId = $transId === '' ? null : 'bitcotasks:'.$transId.':chargeback';

        if ($secret === '' || ! hash_equals(md5($subId.$transId.$rewardRaw.$secret), $signature)) {
            $this->log($request, null, 'rejected', $logTxnId, $offerName, '0.0000');

            return response('invalid signature', 403);
        }

        // Test postback from the BitcoTasks dashboard: acknowledge it
        // and change no balance.
        if ((string) $request->input('debug', '0') === '1') {
            $this->log($request, is_numeric($subId) ? (int) $subId : null, 'test', $logTxnId, $offerName, '0.0000');

            return response('ok', 200);
        }

        $amount = is_numeric($rewardRaw) ? number_format((float) $rewardRaw, 4, '.', '') : '0.0000';
        $user = $subId !== '' && is_numeric($subId) ? User::find((int) $subId) : null;

        if (! $user || bccomp($amount, '0', 4) <= 0) {
            $this->log($request, null, 'rejected', $logTxnId, $offerName, $amount);

            return response('ok', 200);
        }

        if ((string) $request->input('status', '') === '2') {
            return $this->bitcotasksChargeback($request, $user, $amount, $logChargebackTxnId, $offerName);
        }

        if ((string) $request->input('status', '') !== '1') {
            $this->log($request, $user->id, 'rejected', $logTxnId, $offerName, $amount);

            return response('ok', 200);
        }

        if ($logTxnId && PostbackLog::where('txn_id', $logTxnId)->where('status', 'credited')->exists()) {
            $this->log($request, $user->id, 'duplicate', $logTxnId, $offerName, $amount);

            return response('ok', 200);
        }

        $offerType = (string) $request->input('offer_type', 'offer');
        $user->credit($amount, 'offerwall', 'BitcoTasks '.($offerType !== '' ? $offerType : 'offer').' completed'.($offerName ? " ({$offerName})" : ''), 'bitcotasks:'.($transId === '' ? uniqid() : $transId));
        $user->payReferralCommission($amount, 'a BitcoTasks completion');

        $this->log($request, $user->id, 'credited', $logTxnId, $offerName, $amount);

        return response('ok', 200);
    }

    /**
     * Chargeback (status=2): subtract the reward once per transId.
     * The subtraction is recorded even when the original credit never
     * arrived (e.g. the user already withdrew it), so the ledger nets
     * out; a repeated chargeback for the same transId subtracts nothing.
     */
    private function bitcotasksChargeback(Request $request, User $user, string $amount, ?string $logTxnId, $offerName)
    {
        if ($logTxnId && PostbackLog::where('txn_id', $logTxnId)->where('status', 'chargeback')->exists()) {
            $this->log($request, $user->id, 'duplicate', $logTxnId, $offerName, '-'.$amount);

            return response('ok', 200);
        }

        $user->forceDebit($amount, 'offerwall', 'BitcoTasks chargeback'.($offerName ? " ({$offerName})" : ''), 'bitcotasks:chargeback');

        $this->log($request, $user->id, 'chargeback', $logTxnId, $offerName, '-'.$amount);

        return response('ok', 200);
    }

    private function log(Request $request, ?int $userId, string $status, ?string $txnId = null, ?string $offerId = null, string $payout = '0.0000'): void
    {
        try {
            PostbackLog::create([
                'txn_id' => $txnId,
                'user_id' => $userId,
                'offer_id' => $offerId,
                'payout' => $payout,
                'status' => $status,
                'payload' => json_encode($request->except(['key', 'signature'])),
                'ip_address' => $request->ip(),
            ]);
        } catch (\Throwable $e) {
            // A duplicate unique txn_id must never break the endpoint.
        }
    }
}
