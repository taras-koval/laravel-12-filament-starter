@extends('_layouts.app')

@section('title', __('Password Confirmation'))

@section('content')
    <div class="container my-auto pb-20">

        <section class="flex flex-col max-w-[340px] mx-auto gap-6">
            <header class="flex flex-col items-center text-center gap-4">
                <h1 class="text-2xl font-medium">{{ __('Password Confirmation') }}</h1>

                <div class="text-sm text-zinc-600">
                    {{ __('This is a secure area of the application. Please confirm your password before continuing.') }}
                </div>
            </header>

            <form action="{{  route('password.confirm') }}" method="post">
                @csrf

                {{-- Password --}}
                <div class="mb-8">
                    <label for="password" class="label-component">{{ __('Password') }}</label>
                    <input type="password" name="password" id="password" required autofocus
                           placeholder="password"
                           autocomplete="current-password"
                           class="input-component w-full @error('password') border-red-500 @enderror">
                    @error('password')
                        <p class="error-component">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Submit Button --}}
                <button type="submit" class="button-primary-component w-full">
                    @include('_components.loader-indicator')
                    {{ __('Confirm') }}
                </button>
            </form>
        </section>

    </div>
@endsection
