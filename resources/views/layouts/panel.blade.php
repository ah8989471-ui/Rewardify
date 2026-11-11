<!DOCTYPE html>
<html lang="en">
<head>
    @include('layouts.partials.head')
</head>
<body class="min-h-screen bg-slate-100 text-slate-800 antialiased">
@php
    $siteName = \App\Models\Setting::get('site_name', 'WatchEarn');
    $panel = $panel ?? 'user';
    $nav = $panel === 'admin' ? [
        ['route' => 'admin.dashboard', 'label' => 'Overview', 'icon' => 'M3 12l9-9 9 9M5 10v10h5v-6h4v6h5V10'],
        ['route' => 'admin.users', 'label' => 'Users', 'icon' => 'M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 011-3.87m4-1.13a4 4 0 10-4-4m13.5 9.87a4 4 0 10-3.4-6.53'],
        ['route' => 'admin.tasks', 'label' => 'Ad Tasks', 'icon' => 'M15 10.5a3 3 0 11-6 0 3 3 0 016 0z M19.5 10.5c0 7.14-7.5 11.25-7.5 11.25S4.5 17.64 4.5 10.5a7.5 7.5 0 1115 0z'],
        ['route' => 'admin.withdrawals', 'label' => 'Withdrawals', 'icon' => 'M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v3.75'],
        ['route' => 'admin.cpagrip', 'label' => 'CPAGrip', 'icon' => 'M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a5.971 5.971 0 01-8.485 0 5.971 5.971 0 010-8.486l4.5-4.5A4.5 4.5 0 017.19 5.688l-1.5 1.5a4.5 4.5 0 00-.242 6.36l.79.79m6.244-8.132a4.5 4.5 0 00-6.364.242l-.79.79a5.971 5.971 0 000 8.486 5.971 5.971 0 008.486 0l1.5-1.5a4.5 4.5 0 00.242-6.364l-.79-.79'],
        ['route' => 'admin.settings', 'label' => 'Global Settings', 'icon' => 'M10.343 3.94c.09-.542.56-.94 1.11-.94h1.093c.55 0 1.02.398 1.11.94l.149.894c.07.424.384.764.78.93.398.164.855.142 1.205-.108l.737-.527a1.125 1.125 0 011.45.12l.773.774c.39.389.44 1.002.12 1.45l-.527.737c-.25.35-.272.806-.107 1.204.165.397.505.71.93.78l.893.15c.543.09.94.56.94 1.109v1.094c0 .55-.397 1.02-.94 1.11l-.894.149c-.424.07-.764.383-.929.78-.165.398-.143.854.107 1.204l.527.738c.32.447.269 1.06-.12 1.45l-.774.773a1.125 1.125 0 01-1.449.12l-.738-.527c-.35-.25-.806-.272-1.203-.107-.398.165-.71.505-.781.929l-.149.894c-.09.542-.56.94-1.11.94h-1.094c-.55 0-1.019-.398-1.11-.94l-.148-.894c-.071-.424-.384-.764-.781-.93-.398-.164-.854-.142-1.204.108l-.738.527c-.447.32-1.06.269-1.45-.12l-.773-.774a1.125 1.125 0 01-.12-1.45l.527-.737c.25-.35.273-.806.108-1.204-.165-.397-.506-.71-.93-.78l-.894-.15c-.542-.09-.94-.56-.94-1.109v-1.094c0-.55.398-1.02.94-1.11l.894-.149c.424-.07.765-.383.93-.78.165-.398.143-.854-.108-1.204l-.526-.738a1.125 1.125 0 01.12-1.45l.773-.773a1.125 1.125 0 011.45-.12l.737.527c.35.25.807.272 1.205.108.397-.165.71-.505.78-.93l.15-.894z M15 12a3 3 0 11-6 0 3 3 0 016 0z'],
    ] : [
        ['route' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'M3 12l9-9 9 9M5 10v10h5v-6h4v6h5V10'],
        ['route' => 'tasks', 'label' => 'Watch Ads / Tasks', 'icon' => 'M8 5.5v13l11-6.5-11-6.5z'],
        ['route' => 'offerwall', 'label' => 'Offerwall', 'icon' => 'M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z'],
        ['route' => 'wallet', 'label' => 'Wallet & History', 'icon' => 'M21 12a2.25 2.25 0 00-2.25-2.25H15a3 3 0 11-6 0 3 3 0 016 0 2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0015 4.5H5.625a3.375 3.375 0 00-3.375 3.375v9.75c0 1.863 1.512 3.375 3.375 3.375H18.75A2.25 2.25 0 0021 18.75V12z'],
        ['route' => 'referrals', 'label' => 'Referrals', 'icon' => 'M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z'],
        ['route' => 'withdraw.index', 'label' => 'Withdraw', 'icon' => 'M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v3.75'],
    ];
@endphp

<div class="flex min-h-screen">
    {{-- Sidebar (desktop) --}}
    <aside class="fixed inset-y-0 left-0 z-40 w-64 -translate-x-full bg-ink text-slate-200 transition-transform duration-200 lg:translate-x-0" id="sidebar">
        <div class="flex h-16 items-center gap-2 border-b border-white/10 px-5">
            <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-brand-600 text-white">
                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5.5v13l11-6.5-11-6.5z"/></svg>
            </span>
            <div class="leading-tight">
                <div class="font-extrabold text-white">{{ $siteName }}</div>
                <div class="text-[11px] uppercase tracking-wider text-slate-400">{{ $panel === 'admin' ? 'Admin Panel' : 'Worker Panel' }}</div>
            </div>
        </div>
        <nav class="space-y-1 px-3 py-4">
            @foreach ($nav as $item)
                <a href="{{ route($item['route']) }}"
                   class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition {{ request()->routeIs($item['route']) || request()->routeIs($item['route'].'.*') ? 'bg-brand-600 text-white' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                    <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}"/></svg>
                    {{ $item['label'] }}
                </a>
            @endforeach
        </nav>
        <div class="absolute inset-x-0 bottom-0 border-t border-white/10 p-4">
            <div class="mb-3 flex items-center gap-3">
                <div class="flex h-9 w-9 items-center justify-center rounded-full bg-brand-600/20 text-sm font-bold text-brand-300">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</div>
                <div class="min-w-0 leading-tight">
                    <div class="truncate text-sm font-semibold text-white">{{ auth()->user()->name }}</div>
                    <div class="truncate text-xs text-slate-400">{{ auth()->user()->email }}</div>
                </div>
            </div>
            @if ($panel === 'user')
                <div class="mb-3 rounded-xl bg-white/5 px-3 py-2 text-sm">Balance: <span class="font-bold text-emerald-400">${{ number_format((float) auth()->user()->balance, 2) }}</span></div>
            @endif
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="w-full rounded-xl bg-white/10 px-3 py-2 text-sm font-medium text-white hover:bg-white/20">Log out</button>
            </form>
        </div>
    </aside>

    {{-- Mobile overlay --}}
    <div id="sidebarOverlay" class="fixed inset-0 z-30 hidden bg-black/50 lg:hidden"></div>

    <div class="flex min-w-0 flex-1 flex-col lg:pl-64">
        {{-- Topbar --}}
        <header class="sticky top-0 z-20 flex h-16 items-center justify-between border-b border-slate-200 bg-white/90 px-4 backdrop-blur sm:px-6">
            <div class="flex items-center gap-3">
                <button id="sidebarToggle" class="rounded-lg p-2 text-slate-600 hover:bg-slate-100 lg:hidden" aria-label="Menu">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5"/></svg>
                </button>
                <h1 class="text-base font-bold text-ink sm:text-lg">@yield('page-title', 'Dashboard')</h1>
            </div>
            <div class="flex items-center gap-3">
                @if ($panel === 'user')
                    <span class="hidden rounded-full bg-emerald-50 px-3 py-1.5 text-sm font-semibold text-emerald-700 ring-1 ring-emerald-200 sm:inline-flex">${{ number_format((float) auth()->user()->balance, 2) }}</span>
                @endif
                @if (auth()->user()->isAdmin() && $panel === 'user')
                    <a href="{{ route('admin.dashboard') }}" class="rounded-lg bg-ink px-3 py-2 text-sm font-medium text-white">Admin</a>
                @endif
                @if ($panel === 'admin')
                    <a href="{{ route('dashboard') }}" class="rounded-lg bg-slate-100 px-3 py-2 text-sm font-medium text-slate-700">Worker view</a>
                @endif
            </div>
        </header>

        <main class="mx-auto w-full max-w-6xl flex-1 px-4 py-6 sm:px-6 sm:py-8">
            @include('layouts.partials.flash')
            @yield('content')
        </main>

        <footer class="border-t border-slate-200 py-4 text-center text-xs text-slate-500">© {{ date('Y') }} {{ $siteName }}</footer>
    </div>
</div>

<script>
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const toggle = document.getElementById('sidebarToggle');
    function setSidebar(open) {
        // Visibility is controlled ONLY by the off-canvas transform.
        // (An earlier version also toggled display classes - the
        // permanent 'hidden' class always won over the added 'block',
        // so the sidebar could never open on mobile.)
        sidebar.classList.toggle('-translate-x-full', !open);
        overlay.classList.toggle('hidden', !open);
    }
    toggle?.addEventListener('click', () => setSidebar(sidebar.classList.contains('-translate-x-full')));
    overlay?.addEventListener('click', () => setSidebar(false));
</script>
</body>
</html>
