<x-app-layout>
    <x-slot:header>
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Example Page
        </h2>
    </x-slot:header>

    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900">
                <h1 class="text-2xl font-bold mb-4">Modern Laravel Blade Components</h1>
                <p class="mb-4">This page demonstrates the modern <code class="bg-gray-100 px-2 py-1 rounded">&lt;x-app-layout&gt;</code> component syntax.</p>
                
                <div class="space-y-4">
                    <div>
                        <h3 class="font-semibold text-lg mb-2">Features:</h3>
                        <ul class="list-disc list-inside space-y-1">
                            @verbatim
                            <li>Uses component syntax instead of @extends/@section</li>
                            <li>Another line with @directives that should be literal</li>
                            @endverbatim
                            <li>Cleaner, more intuitive structure</li>
                            <li>Named slots for flexible content areas</li>
                            <li>Better IDE support and autocompletion</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
