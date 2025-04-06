@extends('_layouts.profile')

@section('title', __('Account Settings'))
@section('profile-header', __('Account Settings'))

@section('profile-content')
    <section class="max-w-md mb-10" x-data="profileForm()">
        <header class="mb-4">
            <h2 class="font-bold mb-2">{{ __('Account details') }}</h2>
            <p class="text-sm text-zinc-500">{{ __('Update your profile information') }}</p>
        </header>

        <form action="{{ route('profile.account.update') }}" method="post" @submit.prevent="submit()">
            {{-- Name --}}
            <div class="mb-4">
                <label for="name" class="label-component">{{ __('Full name') }}</label>
                <input type="text" name="name" id="name" required
                       x-model="form.name"
                       placeholder="full name"
                       class="input-component w-full" :class="errors.name ? 'border-red-500' : ''">

                <template x-if="errors.name">
                    <p class="error-component" x-text="errors.name[0]"></p>
                </template>
            </div>

            {{-- Email Address --}}
            <div class="mb-6">
                <label for="email" class="label-component">{{ __('Email address') }}</label>
                <input type="email" name="email" id="email" required
                       placeholder="email@example.com"
                       x-model="form.email"
                       class="input-component w-full" :class="errors.email ? 'border-red-500' : ''">

                <template x-if="errors.email">
                    <p class="error-component" x-text="errors.email[0]"></p>
                </template>
            </div>

            {{-- Submit Button --}}
            <button type="submit" class="button-primary-component min-w-20" :disabled="loading">
                <span x-show="!showLoader">{{ __('Save') }}</span>
                <span x-show="showLoader" x-cloak>
                    @include('_components.loader-indicator')
                </span>
            </button>
        </form>
    </section>

    <section class="max-w-md mb-10" x-data="passwordForm()">
        <header class="mb-4">
            <h2 class="font-bold mb-2">
                @if(auth()->user()->password) {{ __('Update password') }} @else {{ __('Create password') }} @endif
            </h2>

            <p class="text-sm text-zinc-500">
                {{ __('Ensure your account is using a long, random password to stay secure') }}
            </p>
        </header>

        <form action="{{ route('profile.account.update-password') }}" method="post" @submit.prevent="submit()">
            {{-- Current Password --}}
            @if(auth()->user()->password)
                <div class="mb-4">
                    <label for="current_password" class="label-component">{{ __('Current Password') }}</label>
                    <input type="password" name="current_password" id="current_password" required
                           x-model="form.current_password"
                           class="input-component w-full" :class="errors.current_password ? 'border-red-500' : ''">

                    <template x-if="errors.current_password">
                        <p class="error-component" x-text="errors.current_password[0]"></p>
                    </template>
                </div>
            @endif

            {{-- New Password --}}
            <div class="mb-4">
                <label for="password" class="label-component">{{ __('New Password') }}</label>
                <input type="password" name="password" id="password" required
                       x-model="form.password"
                       class="input-component w-full" :class="errors.password ? 'border-red-500' : ''">

                <template x-if="errors.password">
                    <p class="error-component" x-text="errors.password[0]"></p>
                </template>
            </div>

            {{-- Confirm New Password --}}
            <div class="mb-6">
                <label for="password_confirmation" class="label-component">{{ __('Confirm Password') }}</label>
                <input type="password" name="password_confirmation" id="password_confirmation" required
                       x-model="form.password_confirmation"
                       class="input-component w-full" :class="errors.password_confirmation ? 'border-red-500' : ''">

                <template x-if="errors.password_confirmation">
                    <p class="error-component" x-text="errors.password_confirmation[0]"></p>
                </template>
            </div>

            {{-- Submit Button --}}
            <button type="submit" class="button-primary-component min-w-20" :disabled="loading">
                <span x-show="!showLoader">{{ __('Save') }}</span>
                <span x-show="showLoader" x-cloak>
                    @include('_components.loader-indicator')
                </span>
            </button>
        </form>
    </section>

    <section class="max-w-md mb-10 hidden">
        <header class="mb-4">
            <h2 class="font-bold mb-2">{{ __('Delete account') }}</h2>
            <p class="text-sm text-zinc-500">{{ __('Delete your account and all of its resources') }}</p>
        </header>

        <form action="{{ route('profile.account.destroy') }}" method="post">
            @method('delete')
            @csrf

            <button type="submit" class="button-secondary-component">
                {{ __('Delete account') }}
            </button>
        </form>
    </section>
@endsection

@push('scripts')
    <script>
        function profileForm() {
            return {
                form: {
                    name: @json(auth()->user()->name),
                    email: @json(auth()->user()->email),
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

        function passwordForm() {
            return {
                form: {
                    current_password: '',
                    password: '',
                    password_confirmation: ''
                },
                hasPassword: {{ auth()->user()->password ? 'true' : 'false' }},
                errors: {},
                loading: false,
                showLoader: false,

                submit() {
                    if (this.loading) return;
                    this.loading = true;
                    const loaderTimeout = setTimeout(() => this.showLoader = true, 150);

                    axios.put('{{ route('profile.account.update-password') }}', this.form)
                        .then(response => {
                            this.errors = {};

                            this.form.current_password = '';
                            this.form.password = '';
                            this.form.password_confirmation = '';

                            toastSuccess(response.data.message);

                            if (!this.hasPassword) {
                                localStorage.setItem('toast_success', response.data.message);
                                location.reload();
                            }
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
                            document.activeElement.blur();
                        });
                }
            }
        }
    </script>
@endpush

