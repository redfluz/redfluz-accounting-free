@extends('layouts.app')

@section('content')
    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="md:col-8">
                @if ($user->must_change_password)
                    <div class="alert alert theme-warning mb-4" data-testid="password-change-required" role="alert">
                        {{ __('users.password_change_required') }}
                    </div>
                @endif

                @if (session('status') === 'profile-updated')
                    <div class="alert alert theme-success mb-4" role="alert">
                        {{ __('users.message.profile_updated') }}
                        <button class="btn-close" data-bs-dismiss="alert" type="button"></button>
                    </div>
                @endif

                <div class="card mb-4 shadow-sm">
                    <div class="card-header bg-1 py-3">
                        <h4 class="card-title fw-bold mb-0">{{ __('users.profile') }}</h4>
                    </div>

                    <div class="card-body p-4">
                        @include('partials.errors')

                        <form autocomplete="off" method="POST" action="{{ route('personnel.profile.update') }}"
                            enctype="multipart/form-data">
                            @csrf
                            @method('PUT')
                            @include('personnel.profile._form')
                            <div class="d-flex justify-content-end">
                                <button class="btn btn-solid theme-accent px-4" type="submit">
                                    {{ __('common.actions.save') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
