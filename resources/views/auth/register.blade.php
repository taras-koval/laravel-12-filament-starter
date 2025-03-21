@extends('_layouts.app')

@section('title', __('Sign up'))

@section('content')
    <div class="container my-auto pb-20">

        <section class="flex flex-col max-w-[340px] mx-auto gap-6">
            <header class="flex flex-col items-center text-center gap-4">
                @include('_components.app-logo')

                <h1 class="text-2xl font-medium">{{ __('Create an account') }}</h1>
            </header>

            <div class="flex flex-col gap-4">
                {{-- Google Button --}}
                <a href="/auth/google/redirect" class="button-secondary-component w-full hover:no-underline">
                    <div class="inline-flex items-center gap-2.5">
                        <img src="{{ asset('images/icons/google.svg') }}" alt="google-logo" class="size-6">
                        <span>{{ __('Sign up with') }} Google</span>
                    </div>
                </a>
            </div>

            <div class="flex items-center w-full">
                <div class="grow border-0 bg-zinc-800/15 h-px"></div>
                <span class="shrink mx-6 font-medium text-sm text-zinc-500 whitespace-nowrap">{{ __('or') }}</span>
                <div class="grow border-0 bg-zinc-800/15 h-px"></div>
            </div>

            <form action="{{ route('register') }}" method="post">
                @csrf

                {{-- Name --}}
                <div class="mb-4">
                    <label for="name" class="label-component">{{ __('Full name') }}</label>
                    <input type="text" name="name" id="name" required autofocus
                           placeholder="full name"
                           value="{{ old('name') }}"
                           class="input-component w-full @error('name') border-red-500 @enderror">
                    @error('name')
                        <p class="error-component">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Email Address --}}
                <div class="mb-4">
                    <label for="email" class="label-component">{{ __('Email address') }}</label>
                    <input type="email" name="email" id="email" required
                           placeholder="email@example.com"
                           value="{{ old('email') }}"
                           class="input-component w-full @error('email') border-red-500 @enderror">
                    @error('email')
                        <p class="error-component">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Password --}}
                <div class="mb-4">
                    <label for="password" class="label-component">{{ __('Password') }}</label>
                    <input type="password" name="password" id="password" required
                           placeholder="password"
                           class="input-component w-full @error('password') border-red-500 @enderror">
                    @error('password')
                        <p class="error-component">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Confirm Password --}}
                <div class="mb-8">
                    <label for="password_confirmation" class="label-component">{{ __('Confirm Password') }}</label>
                    <input type="password" name="password_confirmation" id="password_confirmation" required
                           placeholder="confirm password"
                           class="input-component w-full @error('password_confirmation') border-red-500 @enderror">
                    @error('password_confirmation')
                        <p class="error-component">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Submit Button --}}
                <button type="submit" class="button-primary-component w-full">
                    @include('_components.loader-indicator')
                    {{ __('Sign up') }}
                </button>
            </form>

            <div class="text-sm text-zinc-600 text-center">
                <span>{{ __('Already have an account?') }}</span>
                <a href="{{ route('login') }}" class="underline-link-component">{{ __('Log in') }}</a>
            </div>
        </section>

    </div>
@endsection
