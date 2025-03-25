@extends('_layouts.app')

@section('title', __('Verification Email'))

@section('content')
    <div class="container my-auto pb-20">

        <section class="flex flex-col max-w-[340px] mx-auto gap-6" x-data="resendEmailForm()">
            <header class="flex flex-col items-center text-center gap-4">
                @include('_components.app-logo')

                <h1 class="text-2xl font-medium">{{ __('Verify Your Email Address') }}</h1>

                <div class="text-sm text-zinc-600">
                    {{ __('Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you? If you didn\'t receive the email, we will gladly send you another.') }}
                </div>
            </header>

            <form action="{{ route('verification.send') }}" method="post" @submit.prevent="submit()">
                <button type="submit" class="button-primary-component w-full" :disabled="loading">
                    <span x-show="!showLoader">{{ __('Resend Verification Email') }}</span>
                    <span x-show="showLoader" x-cloak>
                        @include('_components.loader-indicator')
                    </span>
                </button>
            </form>
        </section>

        <section class="flex flex-col max-w-[340px] mx-auto mt-6 gap-6" x-data="updateEmailForm()">
            <div class="flex items-center w-full pt-2">
                <div class="grow border-0 bg-zinc-800/15 h-px"></div>
                <span class="shrink mx-6 font-medium text-sm text-zinc-500 whitespace-nowrap">or</span>
                <div class="grow border-0 bg-zinc-800/15 h-px"></div>
            </div>

            <form action="{{ route('profile.account.update') }}" method="post" @submit.prevent="submit()">
                {{-- Email Address --}}
                <div class="mb-6">
                    <label for="email" class="label-component">{{ __('Update your email address') }}</label>
                    <input type="email" name="email" id="email" required
                           placeholder="email@example.com"
                           x-model="form.email"
                           class="input-component w-full" :class="errors.email ? 'border-red-500' : ''">

                    <template x-if="errors.email">
                        <p class="error-component" x-text="errors.email[0]"></p>
                    </template>
                </div>

                {{-- Submit Button --}}
                <button type="submit" class="button-secondary-component w-full" :disabled="loading">
                    <span x-show="!showLoader">{{ __('Save') }}</span>
                    <span x-show="showLoader" x-cloak>
                        @include('_components.loader-indicator')
                    </span>
                </button>
            </form>
        </section>

    </div>
@endsection

@push('scripts')
    <script>
        function resendEmailForm() {
            return {
                loading: false,
                showLoader: false,

                submit() {
                    if (this.loading) return;
                    this.loading = true;
                    const loaderTimeout = setTimeout(() => this.showLoader = true, 150);

                    axios.post('{{ route('verification.send') }}')
                        .then(response => {
                            toastSuccess(response.data.message);
                        })
                        .catch(error => {
                            toastError(error.response?.data?.message || error.response?.statusText);
                        })
                        .finally(() => {
                            clearTimeout(loaderTimeout);
                            this.loading = false;
                            this.showLoader = false;
                        });
                }
            }
        }

        function updateEmailForm() {
            return {
                form: {
                    email: '{{ old('email', auth()->user()->email) }}',
                },
                errors: {},
                loading: false,
                showLoader: false,

                submit() {
                    if (this.loading) return;
                    this.loading = true;
                    const loaderTimeout = setTimeout(() => this.showLoader = true, 150);

                    axios.patch('{{ route('profile.account.update') }}', this.form)
                        .then(response => {
                            this.errors = {};
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
