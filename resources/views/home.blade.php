@extends('layouts.guest')

@section('title', $siteName.' — '.$tagline)

@section('content')
<div class="mx-auto max-w-3xl text-center">
    <span class="inline-flex items-center rounded-full bg-brand-50 px-3 py-1 text-xs font-semibold text-brand-700 ring-1 ring-brand-100">Watch-to-Earn Micro Tasks</span>
    <h1 class="mt-5 text-4xl font-extrabold tracking-tight text-ink sm:text-5xl">Watch ads. Complete offers.<br class="hidden sm:block"> Get paid in <span class="text-brand-600">USD</span>.</h1>
    <p class="mx-auto mt-4 max-w-xl text-base text-slate-600">{{ $tagline }} Join with a referral code, watch short ads and complete CPAGrip offers, then withdraw to PayPal, Payoneer, USDT or your local wallet.</p>
    <div class="mt-7 flex flex-wrap items-center justify-center gap-3">
        <a href="{{ route('register') }}" class="rounded-xl bg-brand-600 px-6 py-3 text-sm font-semibold text-white shadow-sm hover:bg-brand-700">Create free account</a>
        <a href="{{ route('login') }}" class="rounded-xl bg-white px-6 py-3 text-sm font-semibold text-slate-700 ring-1 ring-slate-300 hover:bg-slate-50">I already have an account</a>
    </div>
</div>

<div class="mx-auto mt-14 grid max-w-4xl gap-4 sm:grid-cols-3">
    @foreach ([
        ['1 — Register', 'Sign up with a referral code and verify your email with a 6-digit code. No code? Get one instantly on WhatsApp.'],
        ['2 — Watch & earn', 'View timed ads or complete offers on the offerwall. Rewards in USD are credited to your wallet automatically.'],
        ['3 — Withdraw', 'Cash out from the minimum threshold via PayPal, Payoneer, USDT (crypto) or a local wallet.'],
    ] as [$t, $d])
        <div class="rounded-2xl bg-white p-5 shadow-card ring-1 ring-slate-200/60">
            <div class="text-sm font-bold text-ink">{{ $t }}</div>
            <p class="mt-1.5 text-sm leading-relaxed text-slate-600">{{ $d }}</p>
        </div>
    @endforeach
</div>
@endsection
