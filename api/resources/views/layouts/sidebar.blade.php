<div class="w-64 bg-white dark:bg-slate-900 shadow-xl dark:shadow-black/30 h-screen fixed top-0 left-0 z-40 flex flex-col transition-all duration-300 border-r border-transparent dark:border-slate-800 overflow-hidden">
    <div class="absolute inset-0 pointer-events-none z-0 opacity-2 dark:hidden" style="background-image: url('{{ asset('image/Dashboard.png') }}'); background-size: cover; background-position: center;"></div>
    <div class="absolute inset-0 pointer-events-none z-0 hidden dark:block opacity-5" style="background-image: url('{{ asset('image/Dashboard whtie.png') }}'); background-size: cover; background-position: center;"></div>
    <div class="h-20 flex items-center justify-center border-b border-gray-100 dark:border-slate-800 px-6 relative z-10">
        <div class="flex items-center space-x-2">
            <svg class="w-8 h-8 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
            <span class="text-lg font-bold text-gray-800 dark:text-slate-100 tracking-wide">CareGuard</span>
        </div>
    </div>

    <div class="flex-1 overflow-y-auto py-6 px-4 space-y-2 relative z-10">
        <a href="{{ route('dashboard') }}" class="flex items-center space-x-3 {{ request()->routeIs('dashboard') ? 'bg-orange-500 text-white shadow-lg shadow-orange-500/40' : 'ui-nav-inactive' }} px-4 py-3 rounded-xl transition-all relative">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
            <span class="font-medium flex-1">Dashboard</span>
            {{-- Live alarm badge --}}
            <span x-data
                  x-show="$store.live && $store.live.pendingCount > 0"
                  x-text="$store.live ? $store.live.pendingCount : ''"
                  class="ml-auto bg-red-500 text-white text-[10px] font-black px-1.5 py-0.5 rounded-full min-w-[18px] text-center leading-none animate-pulse"
                  style="display:none;">
            </span>
        </a>

        <a href="{{ route('analytics') }}" class="flex items-center space-x-3 {{ request()->routeIs('analytics') ? 'bg-orange-500 text-white shadow-lg shadow-orange-500/40' : 'ui-nav-inactive' }} px-4 py-3 rounded-xl transition-all">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
            <span class="font-medium">Analytics</span>
        </a>

        <a href="{{ route('settings') }}" class="flex items-center space-x-3 {{ request()->routeIs('settings') ? 'bg-orange-500 text-white shadow-lg shadow-orange-500/40' : 'ui-nav-inactive' }} px-4 py-3 rounded-xl transition-all">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
            <span class="font-medium">Settings</span>
        </a>
    </div>
</div>
