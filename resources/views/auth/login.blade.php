@extends('_layouts.app')

@section('title', __('Log in'))

@section('content')
    <div class="container my-auto pb-20">

        <section class="flex flex-col max-w-[340px] mx-auto gap-6">
            <header class="flex flex-col items-center text-center gap-4">
                @include('_components.app-logo')

                <h1 class="text-2xl font-medium">{{ __('Log in to your account') }}</h1>
            </header>

            <div class="flex flex-col gap-4">
                {{-- Google Button --}}
                <button type="submit" class="button-secondary-component w-full">
                    @include('_components.loader-indicator')

                    <div class="inline-flex items-center gap-2.5">
                        <img src="{{ asset('images/icons/google.svg') }}" alt="google-logo" class="size-6">
                        <span>{{ __('Continue with') }} Google</span>
                    </div>
                </button>

                {{-- GitHub Button --}}
                {{--<button type="submit" class="button-secondary-component w-full">
                    @include('_components.loader')

                    <div class="inline-flex items-center gap-2.5">
                        <img src="{{ asset('images/icons/github.svg') }}" alt="github-logo" class="size-6">
                        <span>{{ __('Continue with') }} GitHub</span>
                    </div>
                </button>--}}
            </div>

            <div class="flex items-center w-full">
                <div class="grow border-0 bg-zinc-800/15 h-px"></div>
                <span class="shrink mx-6 font-medium text-sm text-zinc-500 whitespace-nowrap">or</span>
                <div class="grow border-0 bg-zinc-800/15 h-px"></div>
            </div>

            <form action="{{ route('login') }}" method="post">
                @csrf

                {{-- Email address --}}
                <div class="mb-6">
                    <label for="email" class="label-component">{{ __('Email address') }}</label>
                    <input type="email" name="email" id="email" required autofocus
                           placeholder="email@example.com"
                           value="{{ old('email') }}"
                           class="input-component w-full @error('email') border-red-500 @enderror">
                    @error('email')
                        <p class="error-component">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Password --}}
                <div class="mb-8">
                    <div class="flex justify-between">
                        <label for="password" class="label-component">{{ __('Password') }}</label>
                        <a href="{{ route('forgot-password') }}" class="underline-link-component">{{ __('Forgot your password?') }}</a>
                    </div>

                    <input type="password" name="password" id="password" required
                           placeholder="password"
                           class="input-component w-full @error('password') border-red-500 @enderror">
                    @error('password')
                        <p class="error-component">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Submit Button --}}
                <button type="submit" class="button-primary-component w-full">
                    @include('_components.loader-indicator')
                    {{ __('Log in') }}
                </button>
            </form>

            <div class="text-sm text-zinc-600 text-center">
                <span>{{ __('Don\'t have an account?') }}</span>
                <a href="{{ route('register') }}" class="underline-link-component">{{ __('Sign up') }}</a>
            </div>
        </section>

    </div>
@endsection
