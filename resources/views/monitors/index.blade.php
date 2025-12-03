<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('All Monitors') }}
            </h2>
            <a href="{{ route('monitors.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                {{ __('Add Monitor') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            {{-- Success message --}}
            @if (session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            {{-- Monitors Grid --}}
            @if($monitors->isEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-12 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <h3 class="mt-2 text-lg font-medium text-gray-900">{{ __('No monitors yet') }}</h3>
                        <p class="mt-1 text-sm text-gray-500">{{ __('Get started by creating your first monitor.') }}</p>
                        <div class="mt-6">
                            <a href="{{ route('monitors.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                </svg>
                                {{ __('Add Your First Monitor') }}
                            </a>
                        </div>
                    </div>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($monitors as $monitor)
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border-l-4 {{ $monitor->isDown() ? 'border-red-500 bg-red-50' : ($monitor->isUp() ? 'border-green-500' : 'border-yellow-500') }} transition-all hover:shadow-md">
                            <div class="p-6">
                                {{-- Monitor Header --}}
                                <div class="flex items-start justify-between mb-4">
                                    <div class="flex-1 min-w-0">
                                        <h3 class="text-lg font-semibold text-gray-900 truncate">
                                            {{ $monitor->name }}
                                        </h3>
                                        <p class="text-sm text-gray-600 truncate mt-1" title="{{ $monitor->url }}">
                                            {{ $monitor->url }}
                                        </p>
                                    </div>
                                    <span class="ml-2 px-2.5 py-1 text-xs font-semibold rounded-full whitespace-nowrap
                                        {{ $monitor->isUp() ? 'bg-green-100 text-green-800' : '' }}
                                        {{ $monitor->isDown() ? 'bg-red-100 text-red-800' : '' }}
                                        {{ $monitor->isPending() ? 'bg-yellow-100 text-yellow-800' : '' }}">
                                        {{ ucfirst($monitor->status) }}
                                    </span>
                                </div>

                                {{-- Monitor Stats --}}
                                <div class="space-y-2 mb-4">
                                    <div class="flex items-center text-sm text-gray-600">
                                        <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        @if($monitor->last_checked_at)
                                            <span>{{ $monitor->last_checked_at->diffForHumans() }}</span>
                                        @else
                                            <span class="text-gray-400">{{ __('Not checked yet') }}</span>
                                        @endif
                                    </div>

                                    @php
                                        $latest_check = $monitor->checks->first();
                                    @endphp

                                    @if($latest_check && $latest_check->response_time_ms)
                                        <div class="flex items-center text-sm text-gray-600">
                                            <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                            </svg>
                                            <span>{{ $latest_check->response_time_ms }}ms</span>
                                        </div>
                                    @endif

                                    <div class="flex items-center text-sm text-gray-600">
                                        <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                        </svg>
                                        <span>{{ __('Every') }} {{ $monitor->check_interval_minutes }} {{ __('min') }}</span>
                                    </div>
                                </div>

                                {{-- Monitor Actions --}}
                                <div class="flex gap-2 pt-4 border-t border-gray-200">
                                    <a href="{{ route('monitors.show', $monitor) }}" class="border border-gray-200 flex-1 text-center px-3 py-2 text-sm font-medium text-indigo-600 hover:text-indigo-900 hover:bg-indigo-50 rounded-md transition-colors">
                                        {{ __('View Details') }}
                                    </a>
                                    <form method="POST" action="{{ route('monitors.check', $monitor) }}" class="flex-1">
                                        @csrf
                                        <button type="submit" class="w-full px-3 py-2 text-sm font-medium text-green-600 hover:text-green-900 hover:bg-green-50 rounded-md transition-colors">
                                            {{ __('Check Now') }}
                                        </button>
                                    </form>
                                    <a href="{{ route('monitors.edit', $monitor) }}" class="flex-1 text-center px-3 py-2 text-sm font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-100 rounded-md transition-colors">
                                        {{ __('Edit') }}
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
