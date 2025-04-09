@extends('_layouts.app')

@section('content')
    <div class="container flex flex-col md:flex-row gap-10">
        <aside class="md:basis-56 md:shrink-0">
            <nav class="w-full space-y-1">
                <a href="{{ route('profile.dashboard') }}" class="button-link-component"
                   @if(request()->routeIs('profile.dashboard')) data-current @endif>{{ __('Dashboard') }}</a>

                <a href="{{ route('profile.account.edit') }}" class="button-link-component"
                   @if(request()->routeIs('profile.account.edit')) data-current @endif>{{ __('Account') }}</a>
            </nav>
        </aside>

        <div class="flex flex-1 flex-col lg:flex-row gap-10">
            <section class="basis-8/12">
                <header>
                    <h1 class="text-xl font-black">@yield('profile-header')</h1>
                    <div class="h-px w-full border-0 bg-zinc-800/5 my-6"></div>
                </header>

                <div class="space-y-10">
                    @yield('profile-content')
                </div>
            </section>

            <aside class="basis-4/12 self-stretch">
                <div class="relative h-[80vh] flex-1 overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
                    <svg class="absolute inset-0 size-full stroke-gray-900/20 dark:stroke-neutral-100/20" fill="none">
                        <defs>
                            <pattern id="pattern-67f6ae67aaedc" x="0" y="0" width="8" height="8" patternUnits="userSpaceOnUse">
                                <path d="M-1 5L5 -1M3 9L8.5 3.5" stroke-width="0.5"></path>
                            </pattern>
                        </defs>
                        <rect stroke="none" fill="url(#pattern-67f6ae67aaedc)" width="100%" height="100%"></rect>
                    </svg>
                </div>
            </aside>
        </div>
    </div>
@endsection

