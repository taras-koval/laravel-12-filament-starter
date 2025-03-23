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
    <nav class="container flex justify-between h-16">
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

                <a href="{{ route('profile.dashboard') }}" class="inline-flex items-center hover:bg-zinc-700 rounded-lg gap-2.5 px-2.5 py-1.5">
                    @if($user->avatar)
                        <img src="{{ $user->avatar }}" alt="{{ $user->name }}" class="size-8 rounded-full">
                    @else
                        <svg class="size-8 text-zinc-300" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" data-slot="icon">
                            <path fill-rule="evenodd" d="M18.685 19.097A9.723 9.723 0 0 0 21.75 12c0-5.385-4.365-9.75-9.75-9.75S2.25 6.615 2.25 12a9.723 9.723 0 0 0 3.065 7.097A9.716 9.716 0 0 0 12 21.75a9.716 9.716 0 0 0 6.685-2.653Zm-12.54-1.285A7.486 7.486 0 0 1 12 15a7.486 7.486 0 0 1 5.855 2.812A8.224 8.224 0 0 1 12 20.25a8.224 8.224 0 0 1-5.855-2.438ZM15.75 9a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" clip-rule="evenodd"/>
                        </svg>
                    @endif
                    {{ $user->name }}
                </a>

                <form action="{{ route('logout') }}" method="post">
                    @csrf
                    <button type="submit" class="cursor-pointer hover:underline">{{ __('Logout') }}</button>
                </form>
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
