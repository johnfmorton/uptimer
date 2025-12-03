<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Dashboard') }}
            </h2>
            <div class="flex gap-3">
                <button id="queueStatusBtn" class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    {{ __('Check Queue Status') }}
                </button>
                <a href="{{ route('monitors.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    {{ __('Add Monitor') }}
                </a>
            </div>
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

            {{-- Queue Status Alert (hidden by default) --}}
            <div id="queueStatusAlert" class="mb-4 px-4 py-3 rounded relative hidden" role="alert">
                <div class="flex items-start">
                    <svg id="queueStatusIcon" class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <div class="flex-1">
                        <p id="queueStatusMessage" class="font-semibold"></p>
                        <div id="queueStatusStats" class="mt-2 text-sm"></div>
                    </div>
                    <button onclick="document.getElementById('queueStatusAlert').classList.add('hidden')" class="ml-4">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>

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
                                    <a href="{{ route('monitors.show', $monitor) }}" class="flex-1 text-center px-3 py-2 text-sm font-medium text-indigo-600 hover:text-indigo-900 hover:bg-indigo-50 rounded-md transition-colors">
                                        {{ __('View Details') }}
                                    </a>
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

    @push('scripts')
    <script>
        document.getElementById('queueStatusBtn').addEventListener('click', async function() {
            const btn = this;
            const originalText = btn.innerHTML;
            
            // Show loading state
            btn.disabled = true;
            btn.innerHTML = `
                <svg class="animate-spin h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Checking...
            `;

            try {
                const response = await fetch('{{ route('queue.status') }}', {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const data = await response.json();
                
                // Show alert
                const alert = document.getElementById('queueStatusAlert');
                const message = document.getElementById('queueStatusMessage');
                const stats = document.getElementById('queueStatusStats');
                const icon = document.getElementById('queueStatusIcon');
                
                // Set message
                message.textContent = data.message;
                
                // Set stats
                stats.innerHTML = `
                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <span class="font-semibold">Pending:</span> ${data.stats.pending_jobs}
                        </div>
                        <div>
                            <span class="font-semibold">Recent Failures:</span> ${data.stats.recent_failures}
                        </div>
                        <div>
                            <span class="font-semibold">Stuck Jobs:</span> ${data.stats.stuck_jobs}
                        </div>
                    </div>
                `;
                
                // Set color based on status
                alert.classList.remove('hidden', 'bg-green-100', 'border-green-400', 'text-green-700', 
                                       'bg-yellow-100', 'border-yellow-400', 'text-yellow-700',
                                       'bg-red-100', 'border-red-400', 'text-red-700');
                
                if (data.status === 'success') {
                    alert.classList.add('bg-green-100', 'border', 'border-green-400', 'text-green-700');
                    icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>';
                } else if (data.status === 'warning') {
                    alert.classList.add('bg-yellow-100', 'border', 'border-yellow-400', 'text-yellow-700');
                    icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>';
                } else {
                    alert.classList.add('bg-red-100', 'border', 'border-red-400', 'text-red-700');
                    icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>';
                }
                
                // Scroll to alert
                alert.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                
            } catch (error) {
                console.error('Error checking queue status:', error);
                alert('Failed to check queue status. Please try again.');
            } finally {
                // Restore button
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        });
    </script>
    @endpush
</x-app-layout>
