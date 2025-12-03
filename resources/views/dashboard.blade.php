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

            {{-- Monitors List --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 text-gray-900">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold">{{ __('Your Monitors') }}</h3>
                        <a href="{{ route('monitors.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            {{ __('Add Monitor') }}
                        </a>
                    </div>

                    @if($monitors->isEmpty())
                        <div class="text-center py-8">
                            <p class="text-gray-600 mb-4">{{ __('You have no monitors configured yet.') }}</p>
                            <p class="text-sm text-gray-500">{{ __('Click "Add Monitor" to start monitoring your first website.') }}</p>
                        </div>
                    @else
                        <div class="space-y-4">
                            @foreach($monitors as $monitor)
                                <div class="border rounded-lg p-4 {{ $monitor->isDown() ? 'border-red-300 bg-red-50' : 'border-gray-200' }}">
                                    <div class="flex justify-between items-start">
                                        <div class="flex-1">
                                            <div class="flex items-center gap-3 mb-2">
                                                <h4 class="text-lg font-semibold">{{ $monitor->name }}</h4>
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full 
                                                    {{ $monitor->isUp() ? 'bg-green-100 text-green-800' : '' }}
                                                    {{ $monitor->isDown() ? 'bg-red-100 text-red-800' : '' }}
                                                    {{ $monitor->isPending() ? 'bg-yellow-100 text-yellow-800' : '' }}">
                                                    {{ ucfirst($monitor->status) }}
                                                </span>
                                            </div>
                                            <p class="text-sm text-gray-600 mb-2">{{ $monitor->url }}</p>
                                            <div class="flex gap-4 text-xs text-gray-500">
                                                @if($monitor->last_checked_at)
                                                    <span>{{ __('Last checked:') }} {{ $monitor->last_checked_at->diffForHumans() }}</span>
                                                @else
                                                    <span>{{ __('Not checked yet') }}</span>
                                                @endif
                                                <span>{{ __('Check interval:') }} {{ $monitor->check_interval_minutes }} {{ __('minutes') }}</span>
                                            </div>
                                        </div>
                                        <div class="flex gap-2">
                                            <a href="{{ route('monitors.show', $monitor) }}" class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">
                                                {{ __('View') }}
                                            </a>
                                            <a href="{{ route('monitors.edit', $monitor) }}" class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">
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

            {{-- Queue Test Section --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h4 class="font-semibold text-gray-800 mb-2">{{ __('Queue System Test') }}</h4>
                    <p class="text-sm text-gray-600 mb-3">
                        {{ __('Test the queue system to ensure background jobs are working correctly.') }}
                    </p>
                    <form method="POST" action="{{ route('queue.test') }}">
                        @csrf
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            {{ __('Dispatch Test Job') }}
                        </button>
                    </form>
                    <p class="text-xs text-gray-500 mt-2">
                        {{ __('After dispatching, check logs with:') }} <code class="bg-gray-200 px-1 py-0.5 rounded">ddev artisan pail</code>
                    </p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
