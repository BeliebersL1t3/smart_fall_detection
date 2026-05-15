<nav class="bg-transparent flex justify-between items-center py-4 px-6 mb-4">
    <div>
        <h2 class="text-2xl font-semibold text-gray-800">@yield('title', 'Dashboard')</h2>
    </div>
    
    <div class="flex items-center space-x-6">
        <div class="flex items-center space-x-4 border-l pl-4 border-gray-200">
            <div class="text-right">
                <p class="text-sm font-semibold text-gray-700">{{ auth()->user()->name }}</p>
                <p class="text-xs text-gray-500">{{ auth()->user()->email }}</p>
            </div>
            
            <form method="POST" action="{{ route('logout') }}" class="m-0 p-0">
                @csrf
                <button type="submit" class="text-gray-400 hover:text-red-500 transition mt-1">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                </button>
            </form>
        </div>
    </div>
</nav>