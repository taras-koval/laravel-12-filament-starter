@extends('_layouts.app')

@section('content')
    <div class="container">

        <div class="flex flex-col md:flex-row gap-10">
            <div class="flex flex-col w-full md:w-56">
                <a href="{{ route('profile.dashboard') }}" class="button-link-component"
                   @if(request()->routeIs('profile.dashboard')) data-current @endif>Dashboard</a>

                <a href="{{ route('profile.account.edit') }}" class="button-link-component"
                   @if(request()->routeIs('profile.account.edit')) data-current @endif>Account</a>
            </div>

            <div class="flex flex-col flex-1 self-stretch">
                <div>
                    <h1 class="text-xl font-black">@yield('profile-header')</h1>
                    <div class="border-0 bg-zinc-800/5 h-px w-full my-6"></div>
                </div>

                @yield('profile-content')
            </div>
        </div>

    </div>
@endsection
