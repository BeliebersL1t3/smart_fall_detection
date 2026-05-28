<nav class="bg-transparent flex justify-between items-center py-4 px-6 mb-4">
    <div>
        <h2 class="text-2xl font-semibold ui-title">@yield('title', 'Dashboard')</h2>
    </div>

    <div class="flex items-center space-x-4">
        <button
            type="button"
            @click="$store.theme.toggle()"
            class="p-2.5 rounded-xl border border-gray-200 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-500 dark:text-slate-300 hover:text-orange-500 dark:hover:text-orange-400 hover:border-orange-200 dark:hover:border-orange-500/40 transition"
            :aria-label="$store.theme.dark ? 'Switch to light mode' : 'Switch to dark mode'"
            title="Toggle dark mode"
        >
            <svg class="w-5 h-5 dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
            </svg>
            <svg class="w-5 h-5 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
            </svg>
        </button>

        <div class="flex items-center space-x-4 border-l pl-4 border-gray-200 dark:border-slate-700">
            <div class="text-right">
                <p class="text-sm font-semibold text-gray-700 dark:text-slate-200">{{ auth()->user()->name }}</p>
                <p class="text-xs text-gray-500 dark:text-slate-400">{{ auth()->user()->email }}</p>
            </div>

            <form method="POST" action="{{ route('logout') }}" class="m-0 p-0">
                @csrf
                <button type="submit" class="text-gray-400 dark:text-slate-500 hover:text-red-500 dark:hover:text-red-400 transition mt-1">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                </button>
            </form>
        </div>
    </div>
</nav>
