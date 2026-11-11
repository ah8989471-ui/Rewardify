<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\Withdrawal;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WithdrawalController extends Controller
{
    public function index(Request $request)
    {
        return view('user.withdraw', [
            'user' => $request->user(),
            'minWithdrawal' => Setting::get('min_withdrawal', '10.00'),
            'withdrawals' => $request->user()->withdrawals()->latest()->paginate(10),
            'methods' => Withdrawal::METHODS,
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $min = (string) Setting::get('min_withdrawal', '10.00');

        $data = $request->validate([
            'method' => ['required', Rule::in(array_keys(Withdrawal::METHODS))],
            'account_details' => ['required', 'string', 'max:1000'],
            'amount' => ['required', 'numeric', 'gt:0'],
        ]);

        $amount = number_format((float) $data['amount'], 4, '.', '');

        if (bccomp($amount, $min, 4) < 0) {
            return back()->withErrors(['amount' => 'Minimum withdrawal is $'.number_format((float) $min, 2).'.'])->withInput();
        }

        try {
            // Funds are held (debited) at request time; a rejection by
            // the admin refunds them in full (see Admin Withdrawals).
            $withdrawal = $user->withdrawals()->create([
                'method' => $data['method'],
                'account_details' => $data['account_details'],
                'amount' => $amount,
                'status' => 'pending',
            ]);

            $user->debit($amount, 'withdrawal', 'Withdrawal request #'.$withdrawal->id.' ('.Withdrawal::METHODS[$data['method']].')', 'withdrawal:'.$withdrawal->id);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['amount' => 'Insufficient balance for this withdrawal.'])->withInput();
        }

        return redirect()->route('withdraw.index')
            ->with('status', 'Withdrawal request submitted. It is now pending admin review.');
    }
}
