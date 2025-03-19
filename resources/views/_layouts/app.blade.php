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
    <nav class="container flex items-center justify-between py-4">
        <div class="flex gap-x-8">
            <a href="{{ route('index') }}" class="text-2xl tracking-tighter font-black leading-tight">
                {{ config('app.name') }}
            </a>
        </div>

        <div class="flex gap-x-8">
            @guest
                <a href="{{ route('login') }}">{{ __('Login') }}</a>
                <a href="{{ route('register') }}">{{ __('Register') }}</a>
            @endguest
            @auth
                <a href="{{ route('profile.dashboard') }}">{{ auth()->user()->name }}</a>
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
