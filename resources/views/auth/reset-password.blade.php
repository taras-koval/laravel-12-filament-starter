@extends('_layouts.app')

@section('title', __('Reset Password'))

@section('content')
    <div class="container my-auto pb-20">

        <section class="flex flex-col max-w-[340px] mx-auto gap-6">
            <header class="flex flex-col items-center text-center gap-4">
                @include('_components.app-logo')

                <h1 class="text-2xl font-medium">{{ __('Reset Password') }}</h1>
            </header>

            <form action="{{ route('password.store') }}" method="post">
                @csrf

                {{-- Password Reset Token --}}
                <input type="hidden" name="token" value="{{ $request->route('token') }}">

                {{-- Email Address --}}
                <input type="hidden" name="email" value="{{ $request->email }}">
                @error('email')
                    <p class="error-component mb-4">{{ $message }}</p>
                @enderror

                {{-- Password --}}
                <div class="mb-4">
                    <label for="password" class="label-component">{{ __('New Password') }}</label>
                    <input type="password" name="password" id="password" required autofocus
                           placeholder="password"
                           autocomplete="new-password"
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
                           autocomplete="new-password"
                           class="input-component w-full @error('password_confirmation') border-red-500 @enderror">
                    @error('password_confirmation')
                        <p class="error-component">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Submit Button --}}
                <button type="submit" class="button-primary-component w-full">
                    @include('_components.loader-indicator')
                    {{ __('Reset password') }}
                </button>
            </form>

            <div class="text-sm text-zinc-600 text-center">
                <span>{{ __('Not working for some reason?') }}</span><br>
                <a href="{{ route('forgot-password') }}" class="underline-link-component">{{ __('Request a new reset link') }}</a>
            </div>
        </section>

    </div>
@endsection
