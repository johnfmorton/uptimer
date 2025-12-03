<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Add New Monitor') }}
            </h2>
            <a href="{{ route('dashboard') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                {{ __('Back to Dashboard') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form method="POST" action="{{ route('monitors.store') }}">
                        @csrf

                        <!-- Monitor Name -->
                        <div class="mb-6">
                            <x-input-label for="name" :value="__('Monitor Name')" />
                            <x-text-input 
                                id="name" 
                                class="block mt-1 w-full" 
                                type="text" 
                                name="name" 
                                :value="old('name')" 
                                required 
                                autofocus 
                                placeholder="My Website"
                            />
                            <p class="mt-1 text-sm text-gray-500">{{ __('A friendly name to identify this monitor') }}</p>
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>

                        <!-- URL -->
                        <div class="mb-6">
                            <x-input-label for="url" :value="__('URL to Monitor')" />
                            <x-text-input 
                                id="url" 
                                class="block mt-1 w-full" 
                                type="url" 
                                name="url" 
                                :value="old('url')" 
                                required 
                                placeholder="https://example.com"
                            />
                            <p class="mt-1 text-sm text-gray-500">{{ __('The URL to check (must use HTTP or HTTPS protocol)') }}</p>
                            <x-input-error :messages="$errors->get('url')" class="mt-2" />
                        </div>

                        <!-- Check Interval -->
                        <div class="mb-6">
                            <x-input-label for="check_interval_minutes" :value="__('Check Interval (minutes)')" />
                            <x-text-input 
                                id="check_interval_minutes" 
                                class="block mt-1 w-full" 
                                type="number" 
                                name="check_interval_minutes" 
                                :value="old('check_interval_minutes', 5)" 
                                required 
                                min="1" 
                                max="1440"
                            />
                            <p class="mt-1 text-sm text-gray-500">{{ __('How often to check this URL (1-1440 minutes)') }}</p>
                            <x-input-error :messages="$errors->get('check_interval_minutes')" class="mt-2" />
                        </div>

                        <!-- Form Actions -->
                        <div class="flex items-center justify-end gap-4 mt-8">
                            <a href="{{ route('dashboard') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                {{ __('Cancel') }}
                            </a>
                            <x-primary-button>
                                {{ __('Create Monitor') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
