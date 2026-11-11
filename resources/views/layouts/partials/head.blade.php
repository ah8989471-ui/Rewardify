<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<link rel="icon" type="image/svg+xml" href="/favicon.svg">
<title>@yield('title', \App\Models\Setting::get('site_name', 'WatchEarn')) — {{ \App\Models\Setting::get('site_name', 'WatchEarn') }}</title>
<script src="https://cdn.tailwindcss.com?plugins=forms"></script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<script>
    tailwind.config = {
        theme: {
            extend: {
                fontFamily: { sans: ['Inter', 'ui-sans-serif', 'system-ui'] },
                colors: {
                    brand: {50:'#eef7ff',100:'#d9ecff',200:'#bcdcff',300:'#8ec6ff',400:'#589fff',500:'#3176fc',600:'#1d55f1',700:'#1743de',800:'#1a39b4',900:'#1b378e'},
                    ink: '#0b1526'
                },
                boxShadow: { card: '0 1px 2px rgba(16,24,40,.06), 0 1px 3px rgba(16,24,40,.1)' }
            }
        }
    }
</script>
<style>
    body { font-family: 'Inter', ui-sans-serif, system-ui, sans-serif; }
    .btn-primary { @apply inline-flex items-center justify-center gap-2 rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 disabled:opacity-50 disabled:pointer-events-none; }
</style>
