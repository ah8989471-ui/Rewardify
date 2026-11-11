<?php

namespace App\Http\Controllers;

use App\Mail\VerificationCodeMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    /* ---------------------------------------------------------------
     | Registration - referral code is MANDATORY.
     * -------------------------------------------------------------- */

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
            // Mandatory referral system: the form cannot be submitted
            // without a valid, existing referral code.
            'referral_code' => ['required', 'string', 'max:16', 'exists:users,referral_code'],
        ], [
            'referral_code.required' => 'A referral code is required to register. Use the WhatsApp button below if you do not have one.',
            'referral_code.exists' => 'This referral code is not valid. Check it or get one via WhatsApp below.',
        ]);

        $referrer = User::findByReferralCode($data['referral_code']);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'referral_code' => User::generateReferralCode(),
            'referred_by' => $referrer?->id,
            'role' => 'user',
            'status' => 'active',
        ]);

        $this->sendVerificationCode($user);

        $request->session()->put('verify_email', $user->email);

        return redirect()->route('verification.notice')
            ->with('status', 'Account created. Enter the 6-digit code we emailed you to verify your account.');
    }

    /* ---------------------------------------------------------------
     | Email verification by 6-digit code.
     * -------------------------------------------------------------- */

    public function showVerify(Request $request)
    {
        $email = $request->session()->get('verify_email')
            ?? $request->user()?->email;

        return view('auth.verify', ['email' => $email]);
    }

    public function verify(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'code' => ['required', 'digits:6'],
        ]);

        $user = User::where('email', $data['email'])->first();

        if (! $user) {
            return back()->withErrors(['email' => 'No account found for this email.']);
        }

        if ($user->email_verified_at) {
            return redirect()->route('login')->with('status', 'This account is already verified. You can log in.');
        }

        if (! $user->verification_code
            || ! $user->verification_code_expires_at
            || $user->verification_code_expires_at->isPast()
            || ! Hash::check($data['code'], $user->verification_code)) {
            return back()->withErrors(['code' => 'The code is incorrect or has expired. Request a new one below.']);
        }

        $user->forceFill([
            'email_verified_at' => now(),
            'verification_code' => null,
            'verification_code_expires_at' => null,
        ])->save();

        $request->session()->forget('verify_email');

        return redirect()->route('login')
            ->with('status', 'Email verified successfully. You can now log in.');
    }

    public function resendCode(Request $request)
    {
        $data = $request->validate(['email' => ['required', 'email']]);
        $user = User::where('email', $data['email'])->first();

        if ($user && ! $user->email_verified_at) {
            $this->sendVerificationCode($user);
            $request->session()->put('verify_email', $user->email);
        }

        return back()->with('status', 'If the account exists and is unverified, a new code has been sent.');
    }

    protected function sendVerificationCode(User $user): void
    {
        $code = (string) random_int(100000, 999999);

        $user->forceFill([
            'verification_code' => Hash::make($code),
            'verification_code_expires_at' => now()->addMinutes(15),
        ])->save();

        Mail::to($user->email)->send(new VerificationCodeMail($user, $code));
    }

    /* ---------------------------------------------------------------
     | Login / logout.
     * -------------------------------------------------------------- */

    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! Auth::attempt($data, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'These credentials do not match our records.'])->onlyInput('email');
        }

        $user = $request->user();

        if ($user->isSuspended()) {
            Auth::logout();

            return back()->withErrors(['email' => 'Your account has been suspended. Please contact support.']);
        }

        $request->session()->regenerate();

        if (! $user->email_verified_at) {
            $request->session()->put('verify_email', $user->email);

            return redirect()->route('verification.notice')
                ->with('status', 'Please verify your email first. Enter the 6-digit code we sent you.');
        }

        return redirect()->intended($user->isAdmin() ? route('admin.dashboard') : route('dashboard'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
