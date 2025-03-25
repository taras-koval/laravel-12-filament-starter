@extends('_layouts.app')

@section('title', __('Reset Password'))

@section('content')
    <div class="container my-auto pb-20">

        <section class="flex flex-col max-w-[340px] mx-auto gap-6">
            <header class="flex flex-col items-center text-center gap-4">
                @include('_components.app-logo')

                <h1 class="text-2xl font-medium">{{ __('Reset Password') }}</h1>
            </header>

            <form action="{{ route('password.store') }}" method="post" x-data="resetForm()" @submit.prevent="submit()">
                {{-- Password --}}
                <div class="mb-4">
                    <label for="password" class="label-component">{{ __('New Password') }}</label>
                    <input type="password" name="password" id="password" required autofocus
                           placeholder="password"
                           autocomplete="new-password"
                           x-model="form.password"
                           class="input-component w-full" :class="errors.password ? 'border-red-500' : ''">

                    <template x-if="errors.password">
                        <p class="error-component" x-text="errors.password[0]"></p>
                    </template>
                </div>

                {{-- Confirm Password --}}
                <div class="mb-8">
                    <label for="password_confirmation" class="label-component">{{ __('Confirm Password') }}</label>
                    <input type="password" name="password_confirmation" id="password_confirmation" required
                           placeholder="confirm password"
                           autocomplete="new-password"
                           x-model="form.password_confirmation"
                           class="input-component w-full" :class="errors.password_confirmation ? 'border-red-500' : ''">

                    <template x-if="errors.password_confirmation">
                        <p class="error-component" x-text="errors.password_confirmation[0]"></p>
                    </template>
                </div>

                {{-- Submit Button --}}
                <button type="submit" class="button-primary-component w-full" :disabled="loading">
                    <span x-show="!showLoader">{{ __('Reset Password') }}</span>
                    <span x-show="showLoader" x-cloak>
                        @include('_components.loader-indicator')
                    </span>
                </button>
            </form>

            <div class="text-sm text-zinc-600 text-center">
                <span>{{ __('Not working for some reason?') }}</span><br>
                <a href="{{ route('forgot-password') }}" class="underline-link-component">{{ __('Request a new reset link') }}</a>
            </div>
        </section>

    </div>
@endsection

@push('scripts')
    <script>
        function resetForm() {
            return {
                form: {
                    email: '{{ $request->email }}',
                    token: '{{ $request->route('token') }}',
                    password: '',
                    password_confirmation: ''
                },
                errors: {},
                loading: false,
                showLoader: false,

                submit() {
                    if (this.loading) return;
                    this.loading = true;
                    const loaderTimeout = setTimeout(() => this.showLoader = true, 150);

                    axios.post('{{ route('password.store') }}', this.form)
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
