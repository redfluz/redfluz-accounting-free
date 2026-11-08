@php
    use App\Enums\UploadType;
    use App\Models\System\User;
@endphp

@props([
    /** @var User|null */
    'user' => null,
])

{{-- Email Address (Read-only) --}}
<div class="mb-3">
    <label class="form-label fg-secondary small text-uppercase fw-bold" for="email">
        {{ __('users.table.column_email') }}
    </label>
    <input class="form-control-plaintext rounded-3 px-3" id="email" type="email"
        value="{{ $user?->email }}" readonly disabled />
    <div class="form-text fg-secondary">{{ __('users.read_only_email') }}.</div>
</div>

<hr class="my-4" />

{{-- Profile Picture Upload --}}
<div class="mb-3">
    <label class="form-label fw-bold" for="picture">
        {{ __('users.table.column_picture') }}
    </label>

    <!-- Existing Server Image (if exists) -->
    @if ($user?->picture)
        <div class="mb-2">
            <small class="fg-secondary d-block mb-1">Current Picture:</small>
            <a data-fancybox="gallery" data-caption="{{ $user->name }}"
                href="{{ asset('storage/' . $user->picture) }}">
                <img class="img-thumbnail rounded-3 border"
                    src="{{ asset('storage/' . $user->picture) }}" alt="{{ $user->name }}"
                    style="max-height: 100px; cursor: pointer" />
            </a>
        </div>
    @endif

    <!-- New Selected Image Preview Container -->
    <div class="d-none mb-2" id="picture-preview-container">
        <small class="fg-secondary d-block mb-1">New Picture Preview:</small>
        <img class="img-thumbnail rounded-3 border" id="picture-preview" src="#"
            alt="New Picture Preview" style="max-height: 100px" />
    </div>

    <input class="form-control @error('picture') is-invalid @enderror" id="picture"
        name="picture" type="file" accept="{{ UploadType::IMAGES->accept() }}"
        data-compress-image data-compress-max-width="1024"
        data-compress-max-height="1024" data-compress-max-bytes="900000"
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
    <div class="form-control form-adorn">
        <input class="form-ghost @error('name') is-invalid @enderror" id="name"
            name="name" type="text" value="{{ old('name', $user?->name) }}" required
            autocomplete="name" />
    </div>
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
        <option value="en" {{ old('locale', $user?->locale) === 'en' ? 'selected' : '' }}>
            English
        </option>
        <option value="ms" {{ old('locale', $user?->locale) === 'ms' ? 'selected' : '' }}>
            Bahasa Melayu
        </option>
        <option value="id" {{ old('locale', $user?->locale) === 'id' ? 'selected' : '' }}>
            Bahasa Indonesia
        </option>
    </select>
    @error('locale')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

{{-- Personal interface theme --}}
<div class="mb-4">
    @php($selectedTheme = old('theme', $user?->theme ?? 'pinkpanther'))
    <label class="form-label fw-bold" for="theme-dropdown">{{ __('users.interface_theme') }}</label>
    <input id="theme" name="theme" type="hidden" value="{{ $selectedTheme }}" data-theme-value>
    <div class="dropdown">
        <button
            class="btn btn-solid theme-secondary menu-toggle @error('theme') is-invalid @enderror"
            id="theme-dropdown" type="button" data-bs-toggle="menu" aria-expanded="false"
            data-theme-toggle>
            <span data-theme-label>{{ __('users.themes.' . $selectedTheme) }}</span>
        </button>
        <div class="menu" aria-labelledby="theme-dropdown">
            @foreach (['redfluz', 'cerulean', 'slate', 'ember', 'verdant', 'pinkpanther', 'ruby', 'paper', 'pastel_fantasy', 'electric_blast', 'rainbow_burst', 'dark', 'cyberpunk', 'zelda', 'red_alert'] as $themeOption)
                <button
                    class="menu-item {{ $selectedTheme === $themeOption ? 'selected' : '' }}"
                    type="button" data-theme-option="{{ $themeOption }}">
                    {{ __('users.themes.' . $themeOption) }}
                </button>
            @endforeach
        </div>
    </div>
    @error('theme')
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
    <div class="form-text">{{ __('users.interface_theme_hint') }}</div>
</div>

{{-- Personal background --}}
<div class="mb-4">
    <label class="form-label fw-bold" for="background_image">
        {{ __('users.background_image') }}
    </label>

    @if ($user?->background_image)
        <div class="mb-2">
            <small class="fg-secondary d-block mb-1">
                {{ __('users.current_background') }}
            </small>
            <img class="img-thumbnail rounded-3 border w-100" id="current-background"
                src="{{ asset('storage/' . $user->background_image) }}"
                alt="{{ __('users.background_image') }}"
                style="max-height: 160px; object-fit: cover" loading="lazy" />
        </div>
    @endif

    <div class="d-none mb-2" id="background-preview-container">
        <small class="fg-secondary d-block mb-1">
            {{ __('users.new_background_preview') }}
        </small>
        <img class="img-thumbnail rounded-3 border w-100" id="background-preview"
            src="#" alt="{{ __('users.new_background_preview') }}"
            style="max-height: 160px; object-fit: cover" />
    </div>

    <input class="form-control @error('background_image') is-invalid @enderror"
        id="background_image" name="background_image" type="file"
        accept="{{ UploadType::IMAGES->accept() }}"
        data-compress-image data-compress-max-width="2000"
        data-compress-max-height="2000" data-compress-max-bytes="1800000"
        onchange="previewSelectedBackground(event)" />

    @error('background_image')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror

    <div class="form-text">{{ __('users.background_image_hint') }}</div>

    @if ($user?->background_image)
        <div class="mt-2">
            <input class="check" id="remove_background" name="remove_background"
                type="checkbox" value="1" @checked((bool) old('remove_background')) />
            <label for="remove_background">{{ __('users.remove_background') }}</label>
        </div>
    @endif
</div>

<hr class="my-4" />

<h6 class="fw-bold mb-3">{{ __('users.change_password') }}</h6>
<p class="fg-secondary small">{{ __('common.labels.leave_blank_password') }}.</p>

{{-- Current Password --}}
<div class="mb-3">
    <label class="form-label" for="current_password">
        {{ __('users.current_password') }}
    </label>
    <div class="form-control form-adorn">
        <input class="form-ghost @error('current_password') is-invalid @enderror"
            id="current_password" name="current_password" type="password"
            autocomplete="current-password" />
    </div>
    @error('current_password')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

{{-- New Password --}}
<div class="mb-3">
    <label class="form-label" for="password"> {{ __('users.new_password') }} </label>
    <div class="form-control form-adorn">
        <input class="form-ghost @error('password') is-invalid @enderror" id="password"
            name="password" type="password" autocomplete="new-password" />
    </div>
    @error('password')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

{{-- Confirm New Password --}}
<div class="mb-4">
    <label class="form-label" for="password_confirmation">
        {{ __('users.confirm_password') }}
    </label>
    <div class="form-control form-adorn">
        <input class="form-ghost" id="password_confirmation" name="password_confirmation"
            type="password" autocomplete="new-password" />
    </div>
</div>

<script>
    (() => {
        const value = document.querySelector('[data-theme-value]');
        const label = document.querySelector('[data-theme-label]');
        const toggle = document.querySelector('[data-theme-toggle]');
        const options = document.querySelectorAll('[data-theme-option]');
        if (!value || !label || !toggle || options.length === 0) return;

        const chooseTheme = (palette, selectedOption) => {
            value.value = palette;
            document.documentElement.dataset.palette = palette;
            label.textContent = selectedOption.textContent.trim();
            options.forEach((option) => {
                const selected = option === selectedOption;
                option.classList.toggle('selected', selected);
                option.setAttribute('aria-current', selected ? 'true' : 'false');
            });
        };

        options.forEach((option) => {
            option.addEventListener('click', () => chooseTheme(option.dataset.themeOption, option));
        });

        const initiallySelected = Array.from(options).find((option) => option.dataset.themeOption === value.value);
        if (initiallySelected) chooseTheme(value.value, initiallySelected);
    })();

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

    function previewSelectedBackground(event) {
        const input = event.target;
        const container = document.getElementById('background-preview-container');
        const preview = document.getElementById('background-preview');

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
