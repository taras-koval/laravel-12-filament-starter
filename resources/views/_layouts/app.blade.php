<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">

    <meta name="application-name" content="{{ config('app.name') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>
        @yield('title', '')
        @if(trim($__env->yieldContent('title'))) | @endif {{ config('app.name') }}
    </title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex flex-col min-h-screen">
<header class="bg-zinc-800 text-white">
    <nav class="container relative flex justify-between h-16">
        <div class="flex items-center gap-x-8">
            <a href="{{ route('index') }}" class="text-2xl tracking-tighter font-black leading-tight">
                {{ config('app.name') }}
            </a>
        </div>

        <div class="flex items-center font-medium gap-x-8">
            @guest
                <a href="{{ route('login') }}" class="hover:underline">{{ __('Login') }}</a>
                <a href="{{ route('register') }}" class="hover:underline">{{ __('Register') }}</a>
            @endguest
            @auth
                @php
                    $user = auth()->user();
                @endphp

                {{-- Profile Dropdown --}}
                <div x-data="{openDropdown: false}">
                    {{-- Dropdown Button --}}
                    <button @click="openDropdown = !openDropdown" class="inline-flex items-center hover:bg-zinc-700 rounded-lg px-2.5 py-1.5">
                        @if($user->avatar)
                            <img src="{{ $user->avatar }}" alt="{{ $user->name }}" class="size-8 rounded-full mr-2.5">
                        @else
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"
                                 class="size-8 text-zinc-300 mr-2.5">
                                <path d="M18.685 19.097A9.723 9.723 0 0 0 21.75 12c0-5.385-4.365-9.75-9.75-9.75S2.25 6.615 2.25 12a9.723 9.723 0 0 0 3.065 7.097A9.716 9.716 0 0 0 12 21.75a9.716 9.716 0 0 0 6.685-2.653ZM6.145 17.812A7.486 7.486 0 0 1 12 15a7.486 7.486 0 0 1 5.855 2.812A8.224 8.224 0 0 1 12 20.25a8.224 8.224 0 0 1-5.855-2.438ZM15.75 9a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z"/>
                            </svg>
                        @endif

                        {{ $user->name }}

                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 18" fill="none" stroke-width="3.5" stroke="currentColor" aria-hidden="true" class="size-3.5 ml-1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                        </svg>
                    </button>

                    {{-- Dropdown Panel --}}
                    <div class="absolute right-4 top-14 font-normal bg-white w-48 rounded-lg shadow p-2 [&>a]:text-zinc-800"
                         x-show="openDropdown" @click.outside="openDropdown = false" x-transition.duration.75 x-cloak>

                        <a href="{{ route('profile.dashboard') }}" class="button-link-component">
                            {{ __('My Profile') }}
                        </a>
                        <a href="{{ route('profile.account.edit') }}" class="button-link-component">
                            {{ __('Account Settings') }}
                        </a>

                        <div class="border-t border-gray-200 my-1"></div>

                        <form action="{{ route('logout') }}" method="post">
                            @csrf
                            <button type="submit" class="button-link-component text-zinc-800">{{ __('Logout') }}</button>
                        </form>
                    </div>

                </div>
            @endauth
        </div>
    </nav>
</header>

<main class="flex flex-col flex-1 py-8">
    @yield('content')
</main>

<footer>
    <div class="container py-4">
        <p class="text-center text-sm">
            &copy; {{ date('Y') }} {{ config('app.name') }}. {{ __('All rights reserved') }}.
        </p>
    </div>
</footer>
</body>
</html>
