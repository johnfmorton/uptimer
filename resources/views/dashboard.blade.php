<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            {{-- Success message --}}
            @if (session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-semibold mb-4">{{ __("You're logged in!") }}</h3>
                    
                    <p class="mb-4 text-gray-600">
                        Welcome to the Uptime Monitor dashboard. This application will monitor your websites and alert you when they go down.
                    </p>

                    {{-- Queue Test Section --}}
                    <div class="mt-6 p-4 bg-gray-50 rounded-lg border border-gray-200">
                        <h4 class="font-semibold text-gray-800 mb-2">Queue System Test</h4>
                        <p class="text-sm text-gray-600 mb-3">
                            Test the queue system to ensure background jobs are working correctly. 
                            Monitor checks will run as queued jobs, so this verifies the infrastructure is ready.
                        </p>
                        <form method="POST" action="{{ route('queue.test') }}">
                            @csrf
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                Dispatch Test Job
                            </button>
                        </form>
                        <p class="text-xs text-gray-500 mt-2">
                            After dispatching, check logs with: <code class="bg-gray-200 px-1 py-0.5 rounded">ddev artisan pail</code>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
