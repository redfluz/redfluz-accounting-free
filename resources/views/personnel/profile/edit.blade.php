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
                        <button class="btn-close" data-bs-dismiss="alert" type="button" aria-label="Close"></button>
                    </div>
                @endif

                <div class="card mb-4 shadow-sm">
                    <div class="card-header bg-white py-3">
                        <h4 class="card-title fw-bold mb-0">{{ __('users.profile') }}</h4>
                    </div>

                    <div class="card-body p-4">
                        @include('partials.errors')

                        <form autocomplete="off" method="POST" action="{{ route('personnel.profile.update') }}" enctype="multipart/form-data">
                            @csrf
                            @method('PUT')

                            {{-- Email Address (Read-only) --}}
                            <div class="mb-3">
                                <label class="form-label fg-secondary small text-uppercase fw-bold" for="email">
                                    {{ __('users.table.column_email') }}
                                </label>
                                <input class="form-control-plaintext rounded px-3" id="email" type="email"
                                    value="{{ $user->email }}" readonly disabled />
                                <div class="form-text fg-secondary">{{ __('users.read_only_email') }}.</div>
                            </div>

                            <hr class="my-4" />

                            {{-- Profile Picture Upload --}}
                            <div class="mb-3">
                                <label class="form-label fw-bold" for="picture">
                                    {{ __('users.table.column_picture') }}
                                </label>

                                <!-- Existing Server Image (if exists) -->
                                @if ($user->picture)
                                    <div class="mb-2">
                                        <small class="fg-secondary d-block mb-1">Current Picture:</small>
                                        <a data-fancybox="gallery" data-caption="{{ $user->name }}"
                                            href="{{ asset('storage/' . $user->picture) }}">
                                            <img class="img-thumbnail rounded border"
                                                src="{{ asset('storage/' . $user->picture) }}" alt="{{ $user->name }}"
                                                style="max-height: 100px; cursor: pointer" />
                                        </a>
                                    </div>
                                @endif

                                <!-- New Selected Image Preview Container -->
                                <div class="d-none mb-2" id="picture-preview-container">
                                    <small class="fg-secondary d-block mb-1">New Picture Preview:</small>
                                    <img class="img-thumbnail rounded border" id="picture-preview" src="#"
                                        alt="New Picture Preview" style="max-height: 100px" />
                                </div>

                                <input class="form-control @error('picture') is-invalid @enderror" id="picture"
                                    name="picture" type="file" accept="{{ \App\Enums\UploadType::IMAGES->accept() }}"
                                    onchange="previewSelectedPicture(event)" />

                                @error('picture')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Full Name --}}
                            <div class="mb-3">
                                <label class="form-label fw-bold" for="name">
                                    {{ __('users.table.column_name') }}
                                </label>
                                <label class="form-control form-adorn">
                                    <input class="form-ghost @error('name') is-invalid @enderror" id="name"
                                        name="name" type="text" value="{{ old('name', $user->name) }}" required
                                        autocomplete="name" />
                                </label>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Preferred Language --}}
                            <div class="mb-3">
                                <label class="form-label fw-bold" for="locale">
                                    {{ __('users.table.column_locale') }}
                                </label>
                                <select class="form-control @error('locale') is-invalid @enderror" id="locale"
                                    name="locale" required>
                                    <option value="en" {{ old('locale', $user->locale) === 'en' ? 'selected' : '' }}>
                                        English
                                    </option>
                                    <option value="ms" {{ old('locale', $user->locale) === 'ms' ? 'selected' : '' }}>
                                        Bahasa Melayu
                                    </option>
                                    <option value="id" {{ old('locale', $user->locale) === 'id' ? 'selected' : '' }}>
                                        Bahasa Indonesia
                                    </option>
                                </select>
                                @error('locale')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <hr class="my-4" />

                            <h6 class="fw-bold mb-3">{{ __('users.change_password') }}</h6>
                            <p class="fg-secondary small">{{ __('common.labels.leave_blank_password') }}.</p>

                            {{-- Current Password --}}
                            <div class="mb-3">
                                <label class="form-label" for="current_password">
                                    {{ __('users.current_password') }}
                                </label>
                                <label class="form-control form-adorn">
                                    <input class="form-ghost @error('current_password') is-invalid @enderror"
                                        id="current_password" name="current_password" type="password"
                                        autocomplete="current-password" />
                                </label>
                                @error('current_password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- New Password --}}
                            <div class="mb-3">
                                <label class="form-label" for="password"> {{ __('users.new_password') }} </label>
                                <label class="form-control form-adorn">
                                    <input class="form-ghost @error('password') is-invalid @enderror" id="password"
                                        name="password" type="password" autocomplete="new-password" />
                                </label>
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Confirm New Password --}}
                            <div class="mb-4">
                                <label class="form-label" for="password_confirmation">
                                    {{ __('users.confirm_password') }}
                                </label>
                                <label class="form-control form-adorn">
                                    <input class="form-ghost" id="password_confirmation" name="password_confirmation"
                                        type="password" autocomplete="new-password" />
                                </label>
                            </div>

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

    <script>
        function previewSelectedPicture(event) {
            const input = event.target;
            const container = document.getElementById('picture-preview-container');
            const preview = document.getElementById('picture-preview');

            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    container.classList.remove('d-none');
                };
                reader.readAsDataURL(input.files[0]);
            } else {
                container.classList.add('d-none');
                preview.src = '#';
            }
        }
    </script>
@endsection
