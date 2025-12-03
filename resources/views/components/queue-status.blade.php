@props(['diagnostics'])

<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border-l-4 {{ $diagnostics['has_issues'] ? 'border-yellow-500' : 'border-green-500' }}">
    <div class="p-6">
        {{-- Header --}}
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900">
                {{ __('Queue & Scheduler Status') }}
            </h3>
            
            {{-- Overall Status Badge --}}
            @if($diagnostics['has_issues'])
                <span class="px-3 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">
                    {{ __('Needs Attention') }}
                </span>
            @else
                <span class="px-3 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                    {{ __('All Systems Running') }}
                </span>
            @endif
        </div>

        {{-- Status Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            {{-- Queue Worker Status --}}
            <div class="flex items-start space-x-3 p-3 rounded-lg {{ $diagnostics['queue_worker_running'] ? 'bg-green-50' : 'bg-red-50' }}">
                <div class="flex-shrink-0">
                    @if($diagnostics['queue_worker_running'])
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    @else
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    @endif
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold {{ $diagnostics['queue_worker_running'] ? 'text-green-900' : 'text-red-900' }}">
                        {{ __('Queue Worker') }}
                    </p>
                    <p class="text-xs {{ $diagnostics['queue_worker_running'] ? 'text-green-700' : 'text-red-700' }}">
                        {{ $diagnostics['queue_worker_running'] ? __('Running') : __('Not Running') }}
                    </p>
                </div>
            </div>

            {{-- Scheduler Status --}}
            <div class="flex items-start space-x-3 p-3 rounded-lg {{ $diagnostics['scheduler_running'] ? 'bg-green-50' : 'bg-red-50' }}">
                <div class="flex-shrink-0">
                    @if($diagnostics['scheduler_running'])
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    @else
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    @endif
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold {{ $diagnostics['scheduler_running'] ? 'text-green-900' : 'text-red-900' }}">
                        {{ __('Scheduler') }}
                    </p>
                    <p class="text-xs {{ $diagnostics['scheduler_running'] ? 'text-green-700' : 'text-red-700' }}">
                        {{ $diagnostics['scheduler_running'] ? __('Running') : __('Not Running') }}
                    </p>
                </div>
            </div>
        </div>

        {{-- Queue Statistics --}}
        <div class="grid grid-cols-3 gap-4 mb-4 p-4 bg-gray-50 rounded-lg">
            <div class="text-center">
                <p class="text-2xl font-bold text-gray-900">{{ $diagnostics['pending_jobs'] }}</p>
                <p class="text-xs text-gray-600">{{ __('Pending Jobs') }}</p>
            </div>
            <div class="text-center">
                <p class="text-2xl font-bold text-gray-900">{{ $diagnostics['failed_jobs_last_hour'] }}</p>
                <p class="text-xs text-gray-600">{{ __('Failed (Last Hour)') }}</p>
            </div>
            <div class="text-center">
                <p class="text-2xl font-bold {{ $diagnostics['stuck_jobs'] > 0 ? 'text-yellow-600' : 'text-gray-900' }}">
                    {{ $diagnostics['stuck_jobs'] }}
                    @if($diagnostics['stuck_jobs'] > 0)
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800 ml-1">
                            {{ __('Warning') }}
                        </span>
                    @endif
                </p>
                <p class="text-xs text-gray-600">{{ __('Stuck Jobs') }}</p>
            </div>
        </div>

        {{-- Success Message (Both Running) --}}
        @if($diagnostics['queue_worker_running'] && $diagnostics['scheduler_running'])
            <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg">
                <div class="flex items-start">
                    <svg class="w-5 h-5 text-green-600 mr-3 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <div>
                        <p class="text-sm font-semibold text-green-900">{{ __('System Properly Configured') }}</p>
                        <p class="text-xs text-green-700 mt-1">
                            {{ __('Both the queue worker and scheduler are running. Your monitors will be checked automatically.') }}
                        </p>
                    </div>
                </div>
            </div>
        @endif

        {{-- Warning Messages --}}
        @if(!$diagnostics['queue_worker_running'])
            <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                <div class="flex items-start">
                    <svg class="w-5 h-5 text-red-600 mr-3 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                    <div class="flex-1">
                        <p class="text-sm font-semibold text-red-900">{{ __('Queue Worker Not Running') }}</p>
                        <p class="text-xs text-red-700 mt-1">
                            {{ __('Background jobs will not be processed. Monitor checks and notifications will not work.') }}
                        </p>
                        <div class="mt-3 p-3 bg-white rounded border border-red-200">
                            <p class="text-xs font-semibold text-gray-900 mb-2">{{ __('To start the queue worker:') }}</p>
                            <code class="block text-xs bg-gray-900 text-green-400 p-2 rounded font-mono">ddev artisan queue:work --tries=1</code>
                            <p class="text-xs text-gray-600 mt-2">
                                {{ __('Or use the development command that includes the queue worker:') }}
                            </p>
                            <code class="block text-xs bg-gray-900 text-green-400 p-2 rounded font-mono mt-1">ddev composer dev</code>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        @if(!$diagnostics['scheduler_running'])
            <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                <div class="flex items-start">
                    <svg class="w-5 h-5 text-red-600 mr-3 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                    <div class="flex-1">
                        <p class="text-sm font-semibold text-red-900">{{ __('Scheduler Not Running') }}</p>
                        <p class="text-xs text-red-700 mt-1">
                            {{ __('Scheduled tasks will not run. Monitors will not be checked automatically at their configured intervals.') }}
                        </p>
                        <div class="mt-3 p-3 bg-white rounded border border-red-200">
                            <p class="text-xs font-semibold text-gray-900 mb-2">{{ __('To start the scheduler:') }}</p>
                            <code class="block text-xs bg-gray-900 text-green-400 p-2 rounded font-mono">ddev artisan schedule:work</code>
                            <p class="text-xs text-gray-600 mt-2">
                                {{ __('Or use the development command that includes the scheduler:') }}
                            </p>
                            <code class="block text-xs bg-gray-900 text-green-400 p-2 rounded font-mono mt-1">ddev composer dev</code>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        @if($diagnostics['stuck_jobs'] > 0)
            <div class="mb-4 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                <div class="flex items-start">
                    <svg class="w-5 h-5 text-yellow-600 mr-3 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                    <div>
                        <p class="text-sm font-semibold text-yellow-900">{{ __('Stuck Jobs Detected') }}</p>
                        <p class="text-xs text-yellow-700 mt-1">
                            {{ __('There are jobs that have been pending for more than 5 minutes. This usually indicates the queue worker is not running or has stopped.') }}
                        </p>
                    </div>
                </div>
            </div>
        @endif

        {{-- General Instructions --}}
        @if($diagnostics['has_issues'])
            <div class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                <div class="flex items-start">
                    <svg class="w-5 h-5 text-blue-600 mr-3 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <div>
                        <p class="text-sm font-semibold text-blue-900">{{ __('Troubleshooting Tips') }}</p>
                        <ul class="text-xs text-blue-700 mt-2 space-y-1 list-disc list-inside">
                            <li>{{ __('Make sure you have started the DDEV environment with') }} <code class="bg-blue-100 px-1 rounded">ddev start</code></li>
                            <li>{{ __('Use') }} <code class="bg-blue-100 px-1 rounded">ddev composer dev</code> {{ __('to start all services at once') }}</li>
                            <li>{{ __('Check the logs with') }} <code class="bg-blue-100 px-1 rounded">ddev artisan pail</code> {{ __('for error messages') }}</li>
                            <li>{{ __('Verify your .env file has') }} <code class="bg-blue-100 px-1 rounded">QUEUE_CONNECTION=database</code></li>
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        {{-- Test Queue Button --}}
        <div class="flex justify-end pt-4 border-t border-gray-200">
            <form method="POST" action="{{ route('queue.test') }}">
                @csrf
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    {{ __('Test Queue') }}
                </button>
            </form>
        </div>
    </div>
</div>
