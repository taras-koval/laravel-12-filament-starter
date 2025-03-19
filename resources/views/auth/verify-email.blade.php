@extends('_layouts.app')

@section('title', __('Verification Email'))

@section('content')
    <div class="container my-auto pb-20">

        <section class="flex flex-col max-w-[340px] mx-auto gap-6">
            <header class="flex flex-col items-center text-center gap-4">
                @include('_components.app-logo')

                <h1 class="text-2xl font-medium">{{ __('Verify Your Email Address') }}</h1>

                <div class="text-sm text-zinc-600">
                    {{ __('Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you? If you didn\'t receive the email, we will gladly send you another.') }}
                </div>
            </header>

            @if (session('status') == 'verification-link-sent')
                <div class="font-medium text-sm text-green-600 text-center">
                    {{ __('A new verification link has been sent to the email address you provided during registration.') }}
                </div>
            @endif

            <form action="{{ route('verification.send') }}" method="post">
                @csrf

                {{-- Submit Button --}}
                <button type="submit" class="button-primary-component w-full">
                    @include('_components.loader-indicator')
                    {{ __('Resend Verification Email') }}
                </button>
            </form>

            @if (session('status') == 'verification-link-sent')
                <div class="flex items-center w-full pt-2">
                    <div class="grow border-0 bg-zinc-800/15 h-px"></div>
                    <span class="shrink mx-6 font-medium text-sm text-zinc-500 whitespace-nowrap">or</span>
                    <div class="grow border-0 bg-zinc-800/15 h-px"></div>
                </div>

                <form action="{{ route('profile.account.update') }}" method="post">
                    @method('patch')
                    @csrf

                    {{-- Email Address --}}
                    <div class="mb-6">
                        <div class="flex justify-between">
                            <label for="email" class="label-component">{{ __('Update your email address') }}</label>
                            @if (session('status') === 'profile-updated')
                                @include('_components.saved-indicator')
                            @endif
                        </div>
                        <input type="email" name="email" id="email" required
                               value="{{ old('email', auth()->user()->email) }}"
                               placeholder="email@example.com"
                               class="input-component w-full @error('email') border-red-500 @enderror">
                        @error('email')
                            <p class="error-component">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Submit Button --}}
                    <div class="flex items-center gap-4">
                        <button type="submit" class="button-secondary-component w-full">
                            @include('_components.loader-indicator')
                            {{ __('Save') }}
                        </button>
                    </div>
                </form>
            @endif
        </section>

    </div>
@endsection
