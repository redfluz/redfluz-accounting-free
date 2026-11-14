@php
    use App\Enums\UploadType;
    use App\Models\Crm\CompanyFile;

    $imageExtensions = ['jpg', 'jpeg', 'png', 'webp'];
@endphp
@props([
    /** @var CompanyFile|null */
    'companyFile' => null,
])

@php
    $attachmentHasErrors = $errors->has('attachment') || $errors->has('remove_attachment');
    $hasAttachment = $companyFile && !empty($companyFile->file_path);
    $isRemoving = (bool) old('remove_attachment', false);
    $ext = strtolower(pathinfo($companyFile?->file_name ?? $companyFile?->file_path ?? '', PATHINFO_EXTENSION));
    $isImage = str_starts_with($companyFile?->mime_type ?? '', 'image/') || in_array($ext, $imageExtensions, true);
@endphp

<ul class="nav nav-tabs mb-4">
    <li class="nav-item">
        <button class="nav-link {{ $attachmentHasErrors ? '' : 'active' }}" data-company-file-tab="details" type="button">
            {{ __('company_files.tabs.details') }}
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link {{ $attachmentHasErrors ? 'active' : '' }}" data-company-file-tab="attachments" type="button">
            {{ __('company_files.tabs.attachments') }}
        </button>
    </li>
</ul>

<section data-company-file-section="details" class="{{ $attachmentHasErrors ? 'd-none' : '' }}">
    <div class="mb-3">
        <label class="form-label fw-bold" for="company_id">
            {{ __('company_files.form.label_company') }}
        </label>
        <select
            class="form-control @error('company_id') is-invalid @enderror"
            id="company_id"
            name="company_id"
            required
        >
            <option value="">{{ __('company_files.form.select_company') }}</option>
            @foreach ($companies as $id => $name)
                <option
                    value="{{ $id }}"
                    {{ old('company_id', $companyFile?->company_id) == $id ? 'selected' : '' }}
                >
                    {{ $name }}
                </option>
            @endforeach
        </select>
        @error('company_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-3">
        <label class="form-label fw-bold" for="title">
            {{ __('company_files.form.label_title') }}
        </label>
        <div class="form-control form-adorn">
            <input
                class="form-ghost @error('title') is-invalid @enderror"
                id="title"
                name="title"
                type="text"
                value="{{ old('title', $companyFile?->title) }}"
                required
            />
        </div>
        @error('title')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-3">
        <label class="form-label fw-bold" for="notes">
            {{ __('company_files.form.label_notes') }}
        </label>
        <textarea class="form-control @error('notes') is-invalid @enderror" id="notes" name="notes" rows="4">{{ old('notes', $companyFile?->notes) }}</textarea>
        @error('notes')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</section>

<section data-company-file-section="attachments" class="{{ $attachmentHasErrors ? '' : 'd-none' }}">
    <div class="mb-4">
        <h6 class="fw-bold mb-1">{{ __('products.attachments.title') }}</h6>
        <p class="fg-secondary small mb-0">{{ __('company_files.form.attachment_size_hint') }}</p>
    </div>

    @if ($hasAttachment)
        <div class="mb-4" id="current-company-file-attachment">
            <div class="form-label fw-bold mb-2">{{ __('company_files.attachments.current') }}</div>
            <div class="border rounded overflow-hidden d-inline-block {{ $isRemoving ? 'opacity-50' : '' }}" id="company-file-attachment-card" style="max-width: 260px;">
                @if ($isImage)
                    <a href="{{ route('crm.company-files.attachment', $companyFile) }}"
                       data-fancybox="company-file-attachment"
                       data-caption="{{ $companyFile->file_name ?: $companyFile->title }}">
                        <img
                            class="d-block w-100"
                            src="{{ route('crm.company-files.attachment', $companyFile) }}"
                            alt="{{ $companyFile->file_name ?: $companyFile->title }}"
                            style="height: 150px; object-fit: cover;"
                        />
                    </a>
                @else
                    <a
                        class="d-flex flex-column align-items-center justify-content-center text-decoration-none p-3 bg-2"
                        href="{{ route('crm.company-files.attachment', $companyFile) }}"
                        target="_blank"
                        style="min-height: 150px; min-width: 220px;"
                    >
                        <i class="fs-xl fg-secondary mb-2" data-lucide="file-text"></i>
                        <small class="text-truncate d-block w-100 text-center">{{ $companyFile->file_name }}</small>
                    </a>
                @endif
                <div class="p-2 border-top bg-white">
                    <div class="form-check mb-0">
                        <input class="form-check-input" id="remove_attachment" type="checkbox" name="remove_attachment" value="1"
                               {{ $isRemoving ? 'checked' : '' }} onchange="toggleCompanyFileAttachment()">
                        <label class="form-check-label small" for="remove_attachment">
                            {{ __('company_files.attachments.remove') }}
                        </label>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="mb-3">
        <label class="form-label fw-bold" for="attachment">{{ __('products.attachments.new') }}</label>
        <input
            id="attachment"
            name="attachment"
            type="file"
            class="form-control @error('attachment') is-invalid @enderror"
            accept="{{ UploadType::DOCUMENT_AND_IMAGES->accept() }}"
        >
        <div class="form-text mt-2">{{ __('company_files.form.attachment_size_hint') }}</div>
        @error('attachment')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
        @error('remove_attachment')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror

        <div id="company-file-new-preview" class="mt-3 d-none">
            <div class="form-label fw-bold mb-2">{{ __('products.attachments.new') }}</div>
            <div class="border rounded overflow-hidden d-inline-block bg-2" style="max-width: 260px;">
                <img id="company-file-new-preview-image" class="d-none w-100" alt="" style="height: 150px; object-fit: cover;">
                <div id="company-file-new-preview-file" class="d-none flex-column align-items-center justify-content-center p-3 text-center" style="min-height: 150px; min-width: 220px;">
                    <i class="fs-xl fg-secondary mb-2" data-lucide="file-text"></i>
                    <small id="company-file-new-preview-name" class="text-truncate d-block w-100"></small>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('[data-company-file-tab]').forEach((tab) => {
            tab.addEventListener('click', () => {
                document.querySelectorAll('[data-company-file-tab]').forEach((item) => item.classList.remove('active'));
                document.querySelectorAll('[data-company-file-section]').forEach((section) => section.classList.add('d-none'));
                tab.classList.add('active');
                const section = document.querySelector(`[data-company-file-section="${tab.dataset.companyFileTab}"]`);
                if (section) section.classList.remove('d-none');
            });
        });

        const input = document.getElementById('attachment');
        if (input) {
            input.addEventListener('change', previewCompanyFileAttachment);
        }
    });

    function toggleCompanyFileAttachment() {
        const checkbox = document.getElementById('remove_attachment');
        const card = document.getElementById('company-file-attachment-card');
        if (!checkbox || !card) return;
        card.classList.toggle('opacity-50', checkbox.checked);
    }

    function previewCompanyFileAttachment(event) {
        const file = event.target.files && event.target.files[0] ? event.target.files[0] : null;
        const wrap = document.getElementById('company-file-new-preview');
        const image = document.getElementById('company-file-new-preview-image');
        const fileBox = document.getElementById('company-file-new-preview-file');
        const name = document.getElementById('company-file-new-preview-name');
        if (!wrap || !image || !fileBox || !name) return;

        if (!file) {
            wrap.classList.add('d-none');
            image.classList.add('d-none');
            image.removeAttribute('src');
            fileBox.classList.add('d-none');
            fileBox.classList.remove('d-flex');
            name.textContent = '';
            return;
        }

        wrap.classList.remove('d-none');
        name.textContent = file.name;
        name.title = file.name;

        if (file.type && file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = (e) => {
                image.src = e.target.result;
                image.alt = file.name;
                image.classList.remove('d-none');
                fileBox.classList.add('d-none');
                fileBox.classList.remove('d-flex');
            };
            reader.readAsDataURL(file);
        } else {
            image.classList.add('d-none');
            image.removeAttribute('src');
            fileBox.classList.remove('d-none');
            fileBox.classList.add('d-flex');
            if (window.lucide && typeof window.lucide.createIcons === 'function') {
                window.lucide.createIcons();
            }
        }
    }
</script>