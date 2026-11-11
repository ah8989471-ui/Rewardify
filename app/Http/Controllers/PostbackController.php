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

    private function log(Request $request, ?int $userId, string $status, ?string $txnId = null, ?string $offerId = null, string $payout = '0.0000'): void
    {
        try {
            PostbackLog::create([
                'txn_id' => $txnId,
                'user_id' => $userId,
                'offer_id' => $offerId,
                'payout' => $payout,
                'status' => $status,
                'payload' => json_encode($request->except('key')),
                'ip_address' => $request->ip(),
            ]);
        } catch (\Throwable $e) {
            // A duplicate unique txn_id must never break the endpoint.
        }
    }
}
