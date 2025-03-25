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
                <a href="/auth/google/redirect" class="button-secondary-component w-full hover:no-underline">
                    <div class="inline-flex items-center gap-2.5">
                        <img src="{{ asset('images/icons/google.svg') }}" alt="google-logo" class="size-6">
                        <span>{{ __('Continue with') }} Google</span>
                    </div>
                </a>
            </div>

            <div class="flex items-center w-full">
                <div class="grow border-0 bg-zinc-800/15 h-px"></div>
                <span class="shrink mx-6 font-medium text-sm text-zinc-500 whitespace-nowrap">or</span>
                <div class="grow border-0 bg-zinc-800/15 h-px"></div>
            </div>

            <form action="{{ route('login') }}" method="post" x-data="loginForm()" @submit.prevent="submit()">
                {{-- Email address --}}
                <div class="mb-6">
                    <label for="email" class="label-component">{{ __('Email address') }}</label>
                    <input type="email" name="email" id="email" required autofocus
                           placeholder="email@example.com"
                           x-model="form.email"
                           class="input-component w-full" :class="errors.email ? 'border-red-500' : ''">

                    <template x-if="errors.email">
                        <p class="error-component" x-text="errors.email[0]"></p>
                    </template>
                </div>

                {{-- Password --}}
                <div class="mb-8">
                    <div class="flex justify-between">
                        <label for="password" class="label-component">{{ __('Password') }}</label>
                        <a href="{{ route('forgot-password') }}" class="underline-link-component">
                            {{ __('Forgot your password?') }}
                        </a>
                    </div>
                    <input type="password" name="password" id="password" required
                           placeholder="password"
                           x-model="form.password"
                           class="input-component w-full" :class="errors.password ? 'border-red-500' : ''">

                    <template x-if="errors.password">
                        <p class="error-component" x-text="errors.password[0]"></p>
                    </template>
                </div>

                {{-- Submit Button --}}
                <button type="submit" class="button-primary-component w-full" :disabled="loading">
                    <span x-show="!showLoader">{{ __('Log in') }}</span>
                    <span x-show="showLoader" x-cloak>
                        @include('_components.loader-indicator')
                    </span>
                </button>
            </form>

            <div class="text-sm text-zinc-600 text-center">
                <span>{{ __('Don\'t have an account?') }}</span>
                <a href="{{ route('register') }}" class="underline-link-component">{{ __('Sign up') }}</a>
            </div>
        </section>

    </div>
@endsection

@push('scripts')
    <script>
        function loginForm() {
            return {
                form: {
                    email: '',
                    password: ''
                },
                errors: {},
                loading: false,
                showLoader: false,

                submit() {
                    if (this.loading) return;
                    this.loading = true;
                    const loaderTimeout = setTimeout(() => this.showLoader = true, 150);

                    axios.post('{{ route('login') }}', this.form)
                        .then(response => {
                            this.errors = {};
                            window.location.href = response.data.redirect;
                        })
                        .catch(error => {
                            if (error.response?.status === 422) {
                                this.errors = error.response.data.errors;
                            } else {
                                toastError(error.response?.data?.message || error.response?.statusText);
                            }
                        })
                        .finally(() => {
                            clearTimeout(loaderTimeout);
                            this.loading = false;
                            this.showLoader = false;
                        });
                }
            }
        }
    </script>
@endpush
