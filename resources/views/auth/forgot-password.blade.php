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

            <form action="{{ route('forgot-password') }}" method="post" x-data="forgotForm()" @submit.prevent="submit()">
                @csrf

                {{-- Email address --}}
                <div class="mb-8">
                    <label for="email" class="label-component">{{ __('Email address') }}</label>
                    <input type="email" name="email" id="email" required autofocus
                           placeholder="email@example.com"
                           x-model="form.email"
                           class="input-component w-full" :class="errors.email ? 'border-red-500' : ''">

                    <template x-if="errors.email">
                        <p class="error-component" x-text="errors.email[0]"></p>
                    </template>
                </div>

                {{-- Submit Button --}}
                <button type="submit" class="button-primary-component w-full" :disabled="loading">
                    <span x-show="!showLoader">{{ __('Send password reset link') }}</span>
                    <span x-show="showLoader" x-cloak>
                        @include('_components.loader-indicator')
                    </span>
                </button>
            </form>

            <div class="text-sm text-zinc-600 text-center">
                <span>{{ __('Or, return to') }}</span>
                <a href="{{ route('login') }}" class="underline-link-component">{{ __('Log in') }}</a>
            </div>
        </section>

    </div>
@endsection

@push('scripts')
    <script>
        function forgotForm() {
            return {
                form: {
                    email: ''
                },
                errors: {},
                loading: false,
                showLoader: false,

                submit() {
                    if (this.loading) return;
                    this.loading = true;
                    const loaderTimeout = setTimeout(() => this.showLoader = true, 150);

                    axios.post('{{ route('forgot-password') }}', this.form)
                        .then(response => {
                            this.errors = {};
                            this.form.email = '';

                            toastSuccess(response.data.message);
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
