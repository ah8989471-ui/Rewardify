<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $task->title }} — Advertisement</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="flex min-h-screen flex-col items-center justify-center gap-6 bg-slate-900 px-4 py-10">
    <p class="max-w-md text-center text-sm text-slate-300">
        Advertisement — keep this tab open until your timer finishes, then return to the WatchEarn tab to claim your reward.
    </p>

    {{-- Admin-provided ad network embed code (e.g. A-Ads unit). --}}
    <div class="flex items-center justify-center">
        {!! $task->embed_code !!}
    </div>

    <p class="text-xs text-slate-500">Closing this tab early stops your watch timer.</p>
</body>
</html>
