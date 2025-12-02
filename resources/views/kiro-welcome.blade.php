<x-app-layout>
    <x-slot name="heaader">named header slot</x-slot>
        <div class="mx-auto flex min-h-screen max-w-5xl items-stretch px-4 py-8 sm:px-6 lg:px-10">
      <div class="flex w-full flex-col rounded-3xl border border-slate-700/60 bg-slate-950/80 shadow-2xl shadow-slate-950/80 overflow-hidden">
        <!-- Header -->
        <header class="flex flex-col gap-4 border-b border-slate-800/80 bg-slate-950/80 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-7">
          <div class="flex items-center gap-3">
            <img src="{{ asset('favicon.png') }}" alt="Kiro Laravel Logo" class="h-12 w-12" />
            <div>
              <h1 class="text-sm font-semibold uppercase tracking-[0.22em] text-slate-100">
                Kiro Laravel Skeleton
              </h1>
              <p class="text-xs text-slate-400">
                Local dev workspace with DDEV, Vite, and Tailwind – already running.
              </p>
            </div>
          </div>

          <div class="flex flex-wrap items-center gap-3">
            <div class="inline-flex items-center gap-2 rounded-full border border-emerald-400/70 bg-emerald-500/15 px-3 py-1 text-xs font-medium text-emerald-100 shadow-md shadow-emerald-500/30">
              <span class="inline-block h-2 w-2 rounded-full bg-emerald-400 shadow-[0_0_12px_rgba(74,222,128,0.9)] animate-pulse"></span>
              Dev environment is live
            </div>
            <span class="hidden text-[11px] uppercase tracking-[0.18em] text-slate-500 sm:inline">
              Next step: Start building your app
            </span>
          </div>
        </header>

        <!-- Main -->
        <main class="grid flex-1 grid-cols-1 gap-4 px-5 py-5 sm:px-7 sm:py-7 lg:grid-cols-[1.35fr_minmax(0,1fr)] lg:gap-6">
          <!-- Left column -->
          <section class="flex flex-col gap-4">
            <!-- Hero -->
            <div class="relative overflow-hidden rounded-2xl border border-slate-700/70 bg-slate-900/80 p-4 sm:p-5">
              <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_top_right,_rgba(56,189,248,0.20),_transparent_55%)]"></div>

              <div class="relative flex flex-col gap-4">

                <div class="space-y-2">
                  <h2 class="text-xl font-semibold text-slate-50 sm:text-2xl">
                    Time to create something great.
                  </h2>
                  <p class="text-sm leading-relaxed text-slate-300 sm:text-[0.95rem]">
                    Laravel, DDEV, Vite, and Tailwind are already configured and running.
                    No boilerplate slog, no environment wrestling. You can focus on your app, not setup.
                  </p>
                </div>

                <div class="space-y-3 rounded-2xl border border-dashed border-emerald-600/80 bg-emerald-950/30 px-3.5 py-3 text-xs text-slate-200 sm:text-[0.8rem]">
                  <p class="font-medium text-emerald-100">
                    🎯 Recommended first step: Define your app with Kiro
                  </p>
                  <div class="space-y-2 text-slate-200">
                    <p>
                      Before diving into code, describe what you're building. Open the Kiro panel and create a new spec that outlines your app's features, requirements, and architecture. For detailed instructions, see the <a href="https://kiro.dev/docs/specs/" class="underline" target="_blank">Kiro spec documentation.</a>
                    </p>
                    <div class="rounded-lg border border-emerald-600/50 bg-slate-950/80 px-3 py-2">
                      <p class="mb-1.5 text-[0.7rem] font-medium uppercase tracking-wider text-emerald-300">
                        How to start:
                      </p>
                      <ol class="space-y-1 text-[0.78rem]">
                        <li class="flex items-start gap-2">
                          <span class="mt-[3px] font-bold text-emerald-400">1.</span>
                          <span>Open the Kiro Specs section in your IDE sidebar</span>
                        </li>
                        <li class="flex items-start gap-2">
                          <span class="mt-[3px] font-bold text-emerald-400">2.</span>
                          <span>Create a new spec describing your app's purpose and features</span>
                        </li>
                        <li class="flex items-start gap-2">
                          <span class="mt-[3px] font-bold text-emerald-400">3.</span>
                          <span>Let Kiro help you iterate on requirements and design</span>
                        </li>
                        <li class="flex items-start gap-2">
                          <span class="mt-[3px] font-bold text-emerald-400">4.</span>
                          <span>Generate implementation tasks and start building</span>
                        </li>
                      </ol>
                    </div>
                    <p class="text-[0.75rem] italic text-slate-300">
                      This structured approach helps you think through your app before writing code, making development faster and more focused.
                    </p>
                  </div>
                </div>

                <div class="space-y-2 rounded-2xl border border-slate-600/80 bg-slate-950/90 px-3.5 py-3 text-xs text-slate-200 sm:text-[0.8rem]">
                  <p class="font-medium text-slate-100">
                    Or jump straight into code by editing:
                  </p>
                  <ul class="grid gap-1.5 sm:grid-cols-2">
                    <li class="flex items-start gap-2">
                      <span class="mt-[3px] h-1.5 w-1.5 rounded-full bg-sky-400"></span>
                      <span>
                        <code class="rounded bg-slate-900/80 px-1.5 py-[1px] text-[0.72rem] text-amber-300/90">routes/web.php</code>
                        – first route, first response.
                      </span>
                    </li>
                    <li class="flex items-start gap-2">
                      <span class="mt-[3px] h-1.5 w-1.5 rounded-full bg-sky-400"></span>
                      <span>
                        <code class="rounded bg-slate-900/80 px-1.5 py-[1px] text-[0.72rem] text-amber-300/90">resources/views</code>
                        – Blade views, layouts, components.
                      </span>
                    </li>
                    <li class="flex items-start gap-2">
                      <span class="mt-[3px] h-1.5 w-1.5 rounded-full bg-sky-400"></span>
                      <span>
                        <code class="rounded bg-slate-900/80 px-1.5 py-[1px] text-[0.72rem] text-emerald-300/90">resources/css</code>
                        – Tailwind-powered styles.
                      </span>
                    </li>
                    <li class="flex items-start gap-2">
                      <span class="mt-[3px] h-1.5 w-1.5 rounded-full bg-sky-400"></span>
                      <span>
                        <code class="rounded bg-slate-900/80 px-1.5 py-[1px] text-[0.72rem] text-sky-300/90">resources/js</code>
                        – Vite entrypoints and interactivity.
                      </span>
                    </li>
                  </ul>
                </div>
              </div>
            </div>

            <!-- Next steps -->
            <div class="rounded-2xl border border-slate-700/70 bg-slate-900/80 p-4 sm:p-5">
              <div class="mb-3 flex items-center justify-between gap-2">
                <h3 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-100">
                  Laravel overview
                </h3>
              </div>

              <p class="mb-3 text-sm leading-relaxed text-slate-300">
                Here are some basic tips on understanding the structure of your Laravel app and where to go from here.
              </p>

              <ul class="space-y-2 text-sm text-slate-200">
                <li class="flex gap-2">
                  <span class="mt-1 inline-block h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
                  <span>
                    Build out a feature flow in
                    <code class="rounded bg-slate-950 px-1.5 py-[1px] text-[0.75rem] text-emerald-300">
                      routes/web.php
                    </code>
                    with controller + view pairs.
                  </span>
                </li>
                <li class="flex gap-2">
                  <span class="mt-1 inline-block h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
                  <span>
                    Create a base layout in
                    <code class="rounded bg-slate-950 px-1.5 py-[1px] text-[0.75rem] text-indigo-300">
                      resources/views/layouts
                    </code>
                    and wire it up with Blade components.
                  </span>
                </li>
                <li class="flex gap-2">
                  <span class="mt-1 inline-block h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
                  <span>
                    Shape your UI system in
                    <code class="rounded bg-slate-950 px-1.5 py-[1px] text-[0.75rem] text-sky-300">
                      resources/css/app.css
                    </code>
                    using Tailwind utilities and small component classes.
                  </span>
                </li>
                <li class="flex gap-2">
                  <span class="mt-1 inline-block h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
                  <span>
                    Add front-end behavior in
                    <code class="rounded bg-slate-950 px-1.5 py-[1px] text-[0.75rem] text-sky-300">
                      resources/js/app.js
                    </code>
                    with Vite hot module reloading active.
                  </span>
                </li>
              </ul>
            </div>

            <!-- Closing -->
            <div class="rounded-2xl border border-slate-700/70 bg-slate-900/80 px-4 py-3.5 sm:px-5">
              <p class="text-sm leading-relaxed text-slate-200">
                The heavy lifting is already handled: Dockerized local stack, asset pipeline,
                and styling system are online.
                <span class="font-medium text-sky-300">From here, every line you add moves the real app forward.</span>
              </p>
            </div>
          </section>

          <!-- Right column: docs/resources -->
          <aside class="flex flex-col gap-4">
            <!-- Docs -->
            <section class="rounded-2xl border border-slate-700/70 bg-slate-900/80 p-4 sm:p-5">
              <div class="mb-3 flex items-center justify-between gap-2">
                <h3 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-100">
                  Core docs
                </h3>
                <span class="rounded-full bg-slate-950/80 px-2.5 py-1 text-[0.7rem] uppercase tracking-[0.18em] text-slate-400">
                  Read as you build
                </span>
              </div>

              <ul class="space-y-2.5 text-sm">
                <li>
                  <div class="flex items-center justify-between gap-2">
                    <span class="font-medium text-slate-50">Kiro docs</span>
                    <span class="rounded-full bg-purple-500/15 px-2 py-[2px] text-[0.65rem] text-purple-300">
                      AI IDE
                    </span>
                  </div>
                  <p class="text-xs text-slate-400">
                    <a href="https://kiro.dev/docs" target="_blank" rel="noreferrer" class="text-sky-300 hover:text-sky-200 hover:underline">
                      https://kiro.dev/docs
                    </a><br />
                    Specs, hooks, steering, and AI-powered development workflows.
                  </p>
                </li>

                <li>
                  <div class="flex items-center justify-between gap-2">
                    <span class="font-medium text-slate-50">Laravel docs</span>
                    <span class="rounded-full bg-emerald-500/15 px-2 py-[2px] text-[0.65rem] text-emerald-300">
                      Framework
                    </span>
                  </div>
                  <p class="text-xs text-slate-400">
                    <a href="https://laravel.com/docs" target="_blank" rel="noreferrer" class="text-sky-300 hover:text-sky-200 hover:underline">
                      https://laravel.com/docs
                    </a><br />
                    Everything from routing to queues, directly from the source.
                  </p>
                </li>

                <li>
                  <div class="flex items-center justify-between gap-2">
                    <span class="font-medium text-slate-50">Laravel + Vite</span>
                    <span class="rounded-full bg-sky-500/15 px-2 py-[2px] text-[0.65rem] text-sky-300">
                      Assets
                    </span>
                  </div>
                  <p class="text-xs text-slate-400">
                    <a href="https://laravel.com/docs/vite" target="_blank" rel="noreferrer" class="text-sky-300 hover:text-sky-200 hover:underline">
                      https://laravel.com/docs/vite
                    </a><br />
                    Asset compilation, hot reloading, and environment handling.
                  </p>
                </li>

                <li>
                  <div class="flex items-center justify-between gap-2">
                    <span class="font-medium text-slate-50">Tailwind CSS</span>
                    <span class="rounded-full bg-indigo-500/15 px-2 py-[2px] text-[0.65rem] text-indigo-300">
                      Styling
                    </span>
                  </div>
                  <p class="text-xs text-slate-400">
                    <a href="https://tailwindcss.com/docs" target="_blank" rel="noreferrer" class="text-sky-300 hover:text-sky-200 hover:underline">
                      https://tailwindcss.com/docs
                    </a><br />
                    Utility classes, layout recipes, and best practices.
                  </p>
                </li>

                <li>
                  <div class="flex items-center justify-between gap-2">
                    <span class="font-medium text-slate-50">Vite</span>
                    <span class="rounded-full bg-sky-500/15 px-2 py-[2px] text-[0.65rem] text-sky-300">
                      Bundler
                    </span>
                  </div>
                  <p class="text-xs text-slate-400">
                    <a href="https://vitejs.dev/guide/" target="_blank" rel="noreferrer" class="text-sky-300 hover:text-sky-200 hover:underline">
                      https://vitejs.dev/guide/
                    </a><br />
                    How Vite thinks about dev server, builds, and code splitting.
                  </p>
                </li>
              </ul>
            </section>

            <!-- DDEV & extras -->
            <section class="rounded-2xl border border-slate-700/70 bg-slate-900/80 p-4 sm:p-5">
              <div class="mb-3 flex items-center justify-between gap-2">
                <h3 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-100">
                  Environment & extras
                </h3>
                <span class="rounded-full bg-slate-950/80 px-2.5 py-1 text-[0.7rem] uppercase tracking-[0.18em] text-slate-400">
                  Under the hood
                </span>
              </div>

              <ul class="space-y-2.5 text-sm">
                <li>
                  <div class="flex items-center justify-between gap-2">
                    <span class="font-medium text-slate-50">DDEV docs</span>
                    <span class="rounded-full bg-amber-500/15 px-2 py-[2px] text-[0.65rem] text-amber-300">
                      Local stack
                    </span>
                  </div>
                  <p class="text-xs text-slate-400">
                    <a href="https://ddev.readthedocs.io" target="_blank" rel="noreferrer" class="text-sky-300 hover:text-sky-200 hover:underline">
                      https://ddev.readthedocs.io
                    </a><br />
                    Containers, routing, database tools, SSL, and automation.
                  </p>
                </li>

                <li>
                  <div class="flex items-center justify-between gap-2">
                    <span class="font-medium text-slate-50">Mailpit</span>
                    <span class="rounded-full bg-purple-500/15 px-2 py-[2px] text-[0.65rem] text-purple-300">
                      Email testing
                    </span>
                  </div>
                  <p class="text-xs text-slate-400">
                    Run <code class="rounded bg-slate-950 px-1.5 py-[1px] text-[0.72rem] text-purple-300">ddev mailpit</code> to open the email testing interface.<br />
                    Catch and inspect all outgoing emails during development.
                  </p>
                </li>

                <li>
                  <div class="flex items-center justify-between gap-2">
                    <span class="font-medium text-slate-50">Laravel starter kits</span>
                    <span class="rounded-full bg-emerald-500/15 px-2 py-[2px] text-[0.65rem] text-emerald-300">
                      Auth
                    </span>
                  </div>
                  <p class="text-xs text-slate-400">
                    <a href="https://laravel.com/docs/starter-kits" target="_blank" rel="noreferrer" class="text-sky-300 hover:text-sky-200 hover:underline">
                      https://laravel.com/docs/starter-kits
                    </a><br />
                    Breeze and friends for quickly adding authentication scaffolding.
                  </p>
                </li>

                <li>
                  <div class="flex items-center justify-between gap-2">
                    <span class="font-medium text-slate-50">PHP manual</span>
                    <span class="rounded-full bg-slate-500/20 px-2 py-[2px] text-[0.65rem] text-slate-200">
                      Language
                    </span>
                  </div>
                  <p class="text-xs text-slate-400">
                    <a href="https://www.php.net/manual/en/" target="_blank" rel="noreferrer" class="text-sky-300 hover:text-sky-200 hover:underline">
                      https://www.php.net/manual/en/
                    </a><br />
                    Function reference, language details, and edge-case behavior.
                  </p>
                </li>
              </ul>
            </section>

            <!-- Makefile / tooling hint (optional content) -->
            <section class="rounded-2xl border border-slate-700/70 bg-slate-900/80 px-4 py-3.5 sm:px-5">
              <p class="text-xs leading-relaxed text-slate-300">
                Your workspace includes helpful
                <span class="font-mono text-[0.7rem] text-sky-300">make</span>
                commands for routine tasks (starting services, running tests, refreshing databases).
                Run
                <span class="font-mono text-[0.7rem] text-emerald-300">
                  make help
                </span>
                or inspect the <span class="font-mono text-[0.7rem] text-sky-300">Makefile</span>
                to discover what’s already automated for you.
              </p>
            </section>
          </aside>
        </main>
      </div>
    </div>
</x-app-layout>
