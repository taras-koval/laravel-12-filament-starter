@extends('_layouts.profile')

@section('title', __('Account Settings'))
@section('profile-header', __('Account Settings'))

@section('profile-content')
    <section class="max-w-md mb-10">
        <header class="mb-4">
            <h2 class="font-bold mb-2">{{ __('Account details') }}</h2>
            <p class="text-sm text-zinc-500">{{ __('Update your profile information') }}</p>
        </header>

        <form action="{{ route('profile.account.update') }}" method="post">
            @method('patch')
            @csrf

            {{-- Name --}}
            <div class="mb-4">
                <label for="name" class="label-component">{{ __('Full name') }}</label>
                <input type="text" name="name" id="name" required
                       value="{{ old('name', auth()->user()->name) }}"
                       placeholder="full name"
                       class="input-component w-full @error('name') border-red-500 @enderror">
                @error('name')
                    <p class="error-component">{{ $message }}</p>
                @enderror
            </div>

            {{-- Email Address --}}
            <div class="mb-6">
                <label for="email" class="label-component">{{ __('Email address') }}</label>
                <input type="email" name="email" id="email" required disabled
                       value="{{ old('email', auth()->user()->email) }}"
                       placeholder="email@example.com"
                       class="input-component w-full @error('email') border-red-500 @enderror">
                @error('email')
                    <p class="error-component">{{ $message }}</p>
                @enderror
            </div>

            {{-- Submit Button --}}
            <div class="flex items-center gap-4">
                <button type="submit" class="button-primary-component">
                    @include('_components.loader-indicator')
                    {{ __('Save') }}
                </button>

                @if (session('status') === 'profile-updated')
                    @include('_components.saved-indicator')
                @endif
            </div>
        </form>
    </section>

    <section class="max-w-md mb-10">
        <header class="mb-4">
            <h2 class="font-bold mb-2">{{ __('Update password') }}</h2>
            <p class="text-sm text-zinc-500">
                {{ __('Ensure your account is using a long, random password to stay secure') }}
            </p>
        </header>

        <form action="{{ route('profile.account.update-password') }}" method="post">
            @method('put')
            @csrf

            <div class="mb-4">
                <label for="current_password" class="label-component">{{ __('Current Password') }}</label>
                <input type="password" name="current_password" id="current_password" required
                       placeholder=""
                       class="input-component w-full @error('current_password') border-red-500 @enderror">
                @error('current_password')
                    <p class="error-component">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-4">
                <label for="password" class="label-component">{{ __('New Password') }}</label>
                <input type="password" name="password" id="password" required
                       placeholder=""
                       class="input-component w-full @error('password') border-red-500 @enderror">
                @error('password')
                    <p class="error-component">{{ $message }}</p>
                @enderror
            </div>

            {{-- Confirm Password --}}
            <div class="mb-6">
                <label for="password_confirmation" class="label-component">{{ __('Confirm Password') }}</label>
                <input type="password" name="password_confirmation" id="password_confirmation" required
                       placeholder=""
                       class="input-component w-full @error('password_confirmation') border-red-500 @enderror">
                @error('password_confirmation')
                    <p class="error-component">{{ $message }}</p>
                @enderror
            </div>

            {{-- Submit Button --}}
            <div class="flex items-center gap-4">
                <button type="submit" class="button-primary-component">
                    @include('_components.loader-indicator')
                    {{ __('Save') }}
                </button>

                @if (session('status') === 'password-updated')
                    @include('_components.saved-indicator')
                @endif
            </div>
        </form>
    </section>

    {{--<section class="max-w-md mb-10">
        <header class="mb-4">
            <h2 class="font-bold mb-2">{{ __('Delete account') }}</h2>
            <p class="text-sm text-zinc-500">{{ __('Delete your account and all of its resources') }}</p>
        </header>

        <form action="{{ route('profile.account.destroy') }}" method="post">
            @method('delete')
            @csrf

            --}}{{-- Submit Button --}}{{--
            <div class="flex items-center gap-4">
                <button type="submit" class="button-secondary-component">
                    @include('_components.loader-indicator')
                    {{ __('Delete account') }}
                </button>
            </div>
        </form>
    </section>--}}

@endsection
