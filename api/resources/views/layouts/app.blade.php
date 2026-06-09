<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CareGuard - @yield('title', 'Dashboard')</title>
    <script>
        (function () {
            var k = 'careguard-theme';
            var s = localStorage.getItem(k);
            var dark = s === 'dark' || (!s && window.matchMedia('(prefers-color-scheme: dark)').matches);
            if (dark) document.documentElement.classList.add('dark');
        })();
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        html.dark ::-webkit-scrollbar-thumb { background: #475569; }
    </style>
</head>
<body class="bg-[#f8fafc] dark:bg-slate-950 text-slate-800 dark:text-slate-200 antialiased overflow-hidden flex h-screen" x-data x-init="$store.theme.init()">
    @include('layouts.sidebar')
    <div class="flex-1 ml-64 flex flex-col h-screen overflow-y-auto overflow-x-hidden relative">
        @include('layouts.header')
        <main class="flex-1 px-8 pb-8">
            @yield('content')
        </main>
        @include('layouts.footer')
    </div>
    @include('layouts.live-monitor')
    @stack('scripts')
</body>
</html>
