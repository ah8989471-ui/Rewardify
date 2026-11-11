<!DOCTYPE html>
<html lang="en">
<head>
    @include('layouts.partials.head')
</head>
<body class="min-h-screen bg-slate-100 text-slate-800 antialiased">
    <header class="border-b border-slate-200 bg-white/90 backdrop-blur">
        <div class="mx-auto flex h-16 max-w-6xl items-center justify-between px-4 sm:px-6">
            <a href="{{ route('home') }}" class="flex items-center gap-2 text-lg font-800 font-extrabold tracking-tight text-ink">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-brand-600 text-white">
                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5.5v13l11-6.5-11-6.5z"/></svg>
                </span>
                {{ \App\Models\Setting::get('site_name', 'WatchEarn') }}
            </a>
            <nav class="flex items-center gap-2 text-sm font-medium">
                <a href="{{ route('login') }}" class="rounded-lg px-3 py-2 text-slate-600 hover:bg-slate-100">Log in</a>
                <a href="{{ route('register') }}" class="rounded-lg bg-brand-600 px-3.5 py-2 text-white hover:bg-brand-700">Get started</a>
            </nav>
        </div>
    </header>

    <main class="mx-auto w-full max-w-6xl px-4 py-8 sm:px-6 sm:py-12">
        @include('layouts.partials.flash')
        @yield('content')
    </main>

    <footer class="border-t border-slate-200 py-6 text-center text-xs text-slate-500">
        © {{ date('Y') }} {{ \App\Models\Setting::get('site_name', 'WatchEarn') }} — {{ \App\Models\Setting::get('site_tagline', 'Watch. Earn. Withdraw.') }}
    </footer>
</body>
</html>
