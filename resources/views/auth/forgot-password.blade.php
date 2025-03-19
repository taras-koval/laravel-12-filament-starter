@extends('_layouts.app')

@section('title', __('Forgot Password'))

@section('content')
    <div class="container my-auto pb-20">

        <section class="flex flex-col max-w-[340px] mx-auto gap-6">
            <header class="flex flex-col items-center text-center gap-4">
                @include('_components.app-logo')

                <h1 class="text-2xl font-medium">{{ __('Forgot Password') }}</h1>
                <p class="text-sm text-zinc-500">{{ __('Enter your email to receive a password reset link') }}</p>
            </header>

            @if(session('status'))
                <div class="font-medium text-sm text-green-600 text-center">{{ session('status') }}</div>
            @endif

            <form action="{{ route('forgot-password') }}" method="post">
                @csrf

                {{-- Email address --}}
                <div class="mb-8">
                    <label for="email" class="label-component">{{ __('Email address') }}</label>
                    <input type="email" name="email" id="email" required autofocus
                           placeholder="email@example.com"
                           value="{{ old('email') }}"
                           class="input-component w-full @error('email') border-red-500 @enderror">
                    @error('email')
                        <p class="error-component">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Submit Button --}}
                <button type="submit" class="button-primary-component w-full">
                    @include('_components.loader-indicator')
                    {{ __('Send password reset link') }}
                </button>
            </form>

            <div class="text-sm text-zinc-600 text-center">
                <span>{{ __('Or, return to') }}</span>
                <a href="{{ route('login') }}" class="underline-link-component">{{ __('Log in') }}</a>
            </div>
        </section>

    </div>
@endsection
