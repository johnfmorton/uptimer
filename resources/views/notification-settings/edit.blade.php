<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Notification Settings') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    <section>
                        <header>
                            <h2 class="text-lg font-medium text-gray-900">
                                {{ __('Notification Preferences') }}
                            </h2>

                            <p class="mt-1 text-sm text-gray-600">
                                {{ __('Configure how you want to receive notifications when your monitors change status.') }}
                            </p>
                        </header>

                        @if (session('success'))
                            <div class="mt-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
                                {{ session('success') }}
                            </div>
                        @endif

                        <form method="post" action="{{ route('notification-settings.update') }}" class="mt-6 space-y-6">
                            @csrf
                            @method('patch')

                            {{-- Email Notifications Section --}}
                            <div class="border-b border-gray-200 pb-6">
                                <h3 class="text-md font-medium text-gray-900 mb-4">
                                    {{ __('Email Notifications') }}
                                </h3>

                                <div class="space-y-4">
                                    <div class="flex items-center">
                                        <input 
                                            id="email_enabled" 
                                            name="email_enabled" 
                                            type="checkbox" 
                                            value="1"
                                            {{ old('email_enabled', $settings->email_enabled ?? true) ? 'checked' : '' }}
                                            class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                                        >
                                        <label for="email_enabled" class="ml-2 block text-sm text-gray-900">
                                            {{ __('Enable email notifications') }}
                                        </label>
                                    </div>
                                    <x-input-error class="mt-2" :messages="$errors->get('email_enabled')" />

                                    <div>
                                        <x-input-label for="email_address" :value="__('Email Address')" />
                                        <x-text-input 
                                            id="email_address" 
                                            name="email_address" 
                                            type="email" 
                                            class="mt-1 block w-full" 
                                            :value="old('email_address', $settings->email_address ?? auth()->user()->email)" 
                                            placeholder="your@email.com"
                                        />
                                        <p class="mt-1 text-sm text-gray-500">
                                            {{ __('Leave empty to use your account email address.') }}
                                        </p>
                                        <x-input-error class="mt-2" :messages="$errors->get('email_address')" />
                                    </div>
                                </div>
                            </div>

                            {{-- Pushover Notifications Section --}}
                            <div class="pb-6">
                                <h3 class="text-md font-medium text-gray-900 mb-4">
                                    {{ __('Pushover Notifications') }}
                                </h3>

                                <p class="text-sm text-gray-600 mb-4">
                                    {{ __('Pushover sends real-time notifications to your mobile device. ') }}
                                    <a href="https://pushover.net" target="_blank" class="text-indigo-600 hover:text-indigo-800 underline">
                                        {{ __('Sign up for Pushover') }}
                                    </a>
                                </p>

                                <div class="space-y-4">
                                    <div class="flex items-center">
                                        <input 
                                            id="pushover_enabled" 
                                            name="pushover_enabled" 
                                            type="checkbox" 
                                            value="1"
                                            {{ old('pushover_enabled', $settings->pushover_enabled ?? false) ? 'checked' : '' }}
                                            class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                                        >
                                        <label for="pushover_enabled" class="ml-2 block text-sm text-gray-900">
                                            {{ __('Enable Pushover notifications') }}
                                        </label>
                                    </div>
                                    <x-input-error class="mt-2" :messages="$errors->get('pushover_enabled')" />

                                    <div>
                                        <x-input-label for="pushover_user_key" :value="__('Pushover User Key')" />
                                        <x-text-input 
                                            id="pushover_user_key" 
                                            name="pushover_user_key" 
                                            type="text" 
                                            class="mt-1 block w-full" 
                                            :value="old('pushover_user_key', $settings->pushover_user_key ?? '')" 
                                            placeholder="Your Pushover user key"
                                        />
                                        <p class="mt-1 text-sm text-gray-500">
                                            {{ __('Found in your Pushover dashboard.') }}
                                        </p>
                                        <x-input-error class="mt-2" :messages="$errors->get('pushover_user_key')" />
                                    </div>

                                    <div>
                                        <x-input-label for="pushover_api_token" :value="__('Pushover API Token')" />
                                        <x-text-input 
                                            id="pushover_api_token" 
                                            name="pushover_api_token" 
                                            type="password" 
                                            class="mt-1 block w-full" 
                                            :value="old('pushover_api_token', '')" 
                                            placeholder="Your Pushover API token"
                                        />
                                        <p class="mt-1 text-sm text-gray-500">
                                            {{ __('Your API token is encrypted and stored securely. Leave empty to keep existing token.') }}
                                        </p>
                                        <x-input-error class="mt-2" :messages="$errors->get('pushover_api_token')" />
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center gap-4">
                                <x-primary-button>{{ __('Save Settings') }}</x-primary-button>

                                @if (session('status') === 'settings-updated')
                                    <p
                                        x-data="{ show: true }"
                                        x-show="show"
                                        x-transition
                                        x-init="setTimeout(() => show = false, 2000)"
                                        class="text-sm text-gray-600"
                                    >{{ __('Saved.') }}</p>
                                @endif
                            </div>
                        </form>
                    </section>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
