<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CareGuard - Register Account</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script>
        if (localStorage.getItem('careguard-theme') === 'dark' || (!('careguard-theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
</head>
<body class="h-full bg-gray-50 dark:bg-gray-950 text-gray-900 dark:text-gray-100 transition-colors duration-300 flex items-center justify-center p-4">

    <div class="w-full max-w-md bg-white dark:bg-gray-900 rounded-2xl shadow-xl border border-gray-100 dark:border-gray-800 p-8 relative overflow-hidden transition-colors duration-300">
        
        <div class="absolute top-0 left-0 right-0 h-2 bg-gradient-to-r from-orange-500 via-amber-500 to-yellow-400"></div>

        <div class="text-center mb-8">
            <div class="inline-flex bg-gradient-to-tr from-orange-500 to-amber-400 p-2.5 rounded-xl shadow-lg shadow-orange-500/20 text-white mb-3">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
            </div>
            <h2 class="text-2xl font-extrabold tracking-tight">Buat Akun CareGuard</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Sistem Pemantau Lansia Terintegrasi</p>
        </div>

        @if ($errors->any())
            <div class="mb-4 p-3 bg-red-50 dark:bg-red-950/30 border border-red-200 dark:border-red-800 rounded-xl text-xs text-red-600 dark:text-red-400 space-y-1">
                @foreach ($errors->all() as $error)
                    <p>- {{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form action="{{ route('register') }}" method="POST" class="space-y-4">
            @csrf

            <div>
                <label for="name" class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1">Nama Lengkap</label>
                <input type="text" name="name" id="name" value="{{ old('name') }}" required placeholder="Nama Anda atau Instansi"
                       class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-950 border border-gray-200 dark:border-gray-800 text-gray-800 dark:text-white rounded-xl focus:ring-2 focus:ring-orange-500 outline-none transition-colors text-sm">
            </div>

            <div>
                <label for="email" class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1">Alamat Email Resmi</label>
                <input type="email" name="email" id="email" value="{{ old('email') }}" required placeholder="nama@email.com"
                       class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-950 border border-gray-200 dark:border-gray-800 text-gray-800 dark:text-white rounded-xl focus:ring-2 focus:ring-orange-500 outline-none transition-colors text-sm">
            </div>

            <div>
                <label for="telegram_chat_id" class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1">Telegram Chat ID (Opsional)</label>
                <input type="text" name="telegram_chat_id" id="telegram_chat_id" value="{{ old('telegram_chat_id') }}" placeholder="Contoh: 123456789"
                       class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-950 border border-gray-200 dark:border-gray-800 text-gray-800 dark:text-white rounded-xl focus:ring-2 focus:ring-orange-500 outline-none transition-colors text-sm">
                <p class="text-[10px] text-gray-400 dark:text-gray-500 mt-1">Dapat dikosongkan dahulu dan diisi nanti melalui halaman pengaturan.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="password" class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1">Password</label>
                    <input type="password" name="password" id="password" required placeholder="Minimal 8 karakter"
                           class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-950 border border-gray-200 dark:border-gray-800 text-gray-800 dark:text-white rounded-xl focus:ring-2 focus:ring-orange-500 outline-none transition-colors text-sm">
                </div>
                <div>
                    <label for="password_confirmation" class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1">Konfirmasi</label>
                    <input type="password" name="password_confirmation" id="password_confirmation" required placeholder="Ulangi password"
                           class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-950 border border-gray-200 dark:border-gray-800 text-gray-800 dark:text-white rounded-xl focus:ring-2 focus:ring-orange-500 outline-none transition-colors text-sm">
                </div>
            </div>

            <div class="pt-2">
                <button type="submit" class="w-full bg-gradient-to-r from-orange-500 to-amber-500 hover:from-orange-600 hover:to-amber-600 text-white font-bold py-3 px-4 rounded-xl shadow-lg shadow-orange-500/20 transition-all transform hover:-translate-y-0.5 text-sm">
                    Daftar & Buat Akun
                </button>
            </div>
        </form>

        <div class="mt-6 text-center text-xs text-gray-500 dark:text-gray-400 border-t border-gray-100 dark:border-gray-800 pt-4">
            Sudah memiliki akun?
            <a href="{{ route('login') }}" class="text-orange-500 hover:underline font-bold ml-1">Masuk di sini</a>
        </div>
    </div>

</body>
</html>
