<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div class="flex-1 min-w-0">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight truncate">
                    {{ $monitor->name }}
                </h2>
                <p class="text-sm text-gray-600 truncate mt-1">{{ $monitor->url }}</p>
            </div>
            <div class="flex items-center gap-2 ml-4">
                <a href="{{ route('dashboard') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    {{ __('Dashboard') }}
                </a>
                <form method="POST" action="{{ route('monitors.check', $monitor) }}" class="inline">
                    @csrf
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        {{ __('Check Now') }}
                    </button>
                </form>
                <a href="{{ route('monitors.edit', $monitor) }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                    {{ __('Edit') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            {{-- Success message --}}
            @if (session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            {{-- Monitor Status Card --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-semibold text-gray-900">{{ __('Current Status') }}</h3>
                        <span class="px-4 py-2 text-sm font-semibold rounded-full
                            {{ $monitor->isUp() ? 'bg-green-100 text-green-800' : '' }}
                            {{ $monitor->isDown() ? 'bg-red-100 text-red-800' : '' }}
                            {{ $monitor->isPending() ? 'bg-yellow-100 text-yellow-800' : '' }}">
                            {{ ucfirst($monitor->status) }}
                        </span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500">{{ __('Last Checked') }}</p>
                                <p class="text-lg font-semibold text-gray-900">
                                    @if($monitor->last_checked_at)
                                        {{ $monitor->last_checked_at->diffForHumans() }}
                                    @else
                                        {{ __('Never') }}
                                    @endif
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500">{{ __('Check Interval') }}</p>
                                <p class="text-lg font-semibold text-gray-900">{{ __('Every') }} {{ $monitor->check_interval_minutes }} {{ __('min') }}</p>
                            </div>
                        </div>

                        @php
                            $latest_check = $monitor->checks->first();
                        @endphp

                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500">{{ __('Response Time') }}</p>
                                <p class="text-lg font-semibold text-gray-900">
                                    @if($latest_check && $latest_check->response_time_ms)
                                        {{ $latest_check->response_time_ms }}ms
                                    @else
                                        {{ __('N/A') }}
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Uptime Statistics Card --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-6">{{ __('Uptime Statistics') }}</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        {{-- 24 Hour Uptime --}}
                        <div class="text-center">
                            <div class="mb-2">
                                <span class="text-3xl font-bold text-gray-900">
                                    @if($uptime_24h !== null)
                                        {{ $uptime_24h == 100 ? '100' : number_format($uptime_24h, 2) }}%
                                    @else
                                        {{ __('N/A') }}
                                    @endif
                                </span>
                            </div>
                            <p class="text-sm font-medium text-gray-500">{{ __('Last 24 Hours') }}</p>
                            @if($uptime_24h !== null)
                                <div class="mt-2 w-full bg-gray-200 rounded-full h-2">
                                    <div class="bg-green-600 h-2 rounded-full" style="width: {{ $uptime_24h }}%"></div>
                                </div>
                            @endif
                        </div>

                        {{-- 7 Day Uptime --}}
                        <div class="text-center">
                            <div class="mb-2">
                                <span class="text-3xl font-bold text-gray-900">
                                    @if($uptime_7d !== null)
                                        {{ $uptime_7d == 100 ? '100' : number_format($uptime_7d, 2) }}%
                                    @else
                                        {{ __('N/A') }}
                                    @endif
                                </span>
                            </div>
                            <p class="text-sm font-medium text-gray-500">{{ __('Last 7 Days') }}</p>
                            @if($uptime_7d !== null)
                                <div class="mt-2 w-full bg-gray-200 rounded-full h-2">
                                    <div class="bg-green-600 h-2 rounded-full" style="width: {{ $uptime_7d }}%"></div>
                                </div>
                            @endif
                        </div>

                        {{-- 30 Day Uptime --}}
                        <div class="text-center">
                            <div class="mb-2">
                                <span class="text-3xl font-bold text-gray-900">
                                    @if($uptime_30d !== null)
                                        {{ $uptime_30d == 100 ? '100' : number_format($uptime_30d, 2) }}%
                                    @else
                                        {{ __('N/A') }}
                                    @endif
                                </span>
                            </div>
                            <p class="text-sm font-medium text-gray-500">{{ __('Last 30 Days') }}</p>
                            @if($uptime_30d !== null)
                                <div class="mt-2 w-full bg-gray-200 rounded-full h-2">
                                    <div class="bg-green-600 h-2 rounded-full" style="width: {{ $uptime_30d }}%"></div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Check History Card --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-6">{{ __('Check History') }}</h3>
                    
                    @if($checks->isEmpty())
                        <div class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                            </svg>
                            <p class="mt-2 text-sm text-gray-500">{{ __('No check history available yet') }}</p>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            {{ __('Timestamp') }}
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            {{ __('Status') }}
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            {{ __('Status Code') }}
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            {{ __('Response Time') }}
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            {{ __('Error Message') }}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($checks as $check)
                                        <tr class="{{ $check->wasFailed() ? 'bg-red-50' : '' }}">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <div class="font-medium">
                                                    {{ $check->checked_at->format('M j, Y') }}
                                                </div>
                                                <div class="text-gray-600">
                                                    {{ $check->checked_at->format('g:i A') }}
                                                </div>
                                                <span class="text-gray-500 text-xs block mt-1">
                                                    {{ $check->checked_at->diffForHumans() }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                                    {{ $check->wasSuccessful() ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                    {{ ucfirst($check->status) }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                @if($check->status_code)
                                                    {{ $check->status_code }}
                                                @else
                                                    <span class="text-gray-400">{{ __('N/A') }}</span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                @if($check->response_time_ms)
                                                    {{ $check->response_time_ms }}ms
                                                @else
                                                    <span class="text-gray-400">{{ __('N/A') }}</span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-900">
                                                @if($check->error_message)
                                                    <span class="text-red-600">{{ $check->error_message }}</span>
                                                @else
                                                    <span class="text-gray-400">{{ __('—') }}</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        {{-- Pagination --}}
                        <div class="mt-6">
                            {{ $checks->links() }}
                        </div>
                    @endif
                </div>
            </div>

            {{-- Danger Zone Card --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border-l-4 border-red-500">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-red-900 mb-2">{{ __('Danger Zone') }}</h3>
                    <p class="text-sm text-gray-600 mb-4">{{ __('Once you delete a monitor, there is no going back. All check history will be permanently deleted.') }}</p>
                    
                    <div x-data="{ showDeleteConfirm: false }">
                        <button 
                            @click="showDeleteConfirm = true"
                            type="button"
                            class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 focus:bg-red-700 active:bg-red-900 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                            {{ __('Delete Monitor') }}
                        </button>

                        {{-- Delete Confirmation Modal --}}
                        <div 
                            x-show="showDeleteConfirm"
                            x-cloak
                            class="fixed inset-0 z-50 overflow-y-auto"
                            aria-labelledby="modal-title" 
                            role="dialog" 
                            aria-modal="true">
                            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                                {{-- Background overlay --}}
                                <div 
                                    x-show="showDeleteConfirm"
                                    x-transition:enter="ease-out duration-300"
                                    x-transition:enter-start="opacity-0"
                                    x-transition:enter-end="opacity-100"
                                    x-transition:leave="ease-in duration-200"
                                    x-transition:leave-start="opacity-100"
                                    x-transition:leave-end="opacity-0"
                                    @click="showDeleteConfirm = false"
                                    class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" 
                                    aria-hidden="true">
                                </div>

                                {{-- Center modal --}}
                                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                                {{-- Modal panel --}}
                                <div 
                                    x-show="showDeleteConfirm"
                                    x-transition:enter="ease-out duration-300"
                                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                                    x-transition:leave="ease-in duration-200"
                                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                                    class="relative inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                                    <div class="sm:flex sm:items-start">
                                        <div class="mx-auto shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                                            <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                            </svg>
                                        </div>
                                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                                {{ __('Delete Monitor') }}
                                            </h3>
                                            <div class="mt-2">
                                                <p class="text-sm text-gray-500">
                                                    {{ __('Are you sure you want to delete this monitor? This action cannot be undone and all check history will be permanently deleted.') }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                                        <form method="POST" action="{{ route('monitors.destroy', $monitor) }}" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button 
                                                type="submit"
                                                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                                                {{ __('Delete') }}
                                            </button>
                                        </form>
                                        <button 
                                            @click="showDeleteConfirm = false"
                                            type="button"
                                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:w-auto sm:text-sm">
                                            {{ __('Cancel') }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <style>
        [x-cloak] { display: none !important; }
    </style>
    @endpush
</x-app-layout>
