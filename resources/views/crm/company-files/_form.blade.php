@php
    use App\Enums\UploadType;
    use App\Models\Crm\CompanyFile;
@endphp
@props([
    /** @var CompanyFile|null */
    'companyFile' => null,
])

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
    <label class="form-label fw-bold" for="{{ $companyFile ? 'attachment' : 'attachments' }}">
        {{ __('company_files.form.label_attachment') }}
    </label>

    @if ($companyFile)
        {{-- Edit mode: show existing attachment + single file upload --}}
        @if ($companyFile->file_path)
            <div class="mb-2">
                <small class="fg-secondary d-block mb-1">Current Attachment:</small>
                @if (str_starts_with($companyFile->mime_type ?? '', 'image/'))
                    <a
                        data-fancybox="gallery"
                        data-caption="{{ $companyFile->title }}"
                        href="{{ route('crm.company-files.attachment', $companyFile) }}"
                    >
                        <img
                            class="img-thumbnail rounded border"
                            src="{{ route('crm.company-files.attachment', $companyFile) }}"
                            alt="{{ $companyFile->title }}"
                            style="max-height: 100px; cursor: pointer"
                        />
                    </a>
                @else
                    <a
                        class="btn btn-sm btn-solid theme-secondary"
                        href="{{ route('crm.company-files.attachment', $companyFile) }}"
                        target="_blank"
                    >
                        <i class="me-1" data-lucide="paperclip"></i> {{ $companyFile->file_name }}
                    </a>
                @endif

                <div class="mt-2">
                    <input
                        class="check"
                        id="remove_attachment"
                        name="remove_attachment"
                        type="checkbox"
                        value="1"
                    />
                    <label class="fg-danger" for="remove_attachment">
                        {{ __('company_files.form.label_remove_attachment') }}
                    </label>
                </div>
            </div>
        @endif

        {{-- New Selected File Preview Container --}}
        <div class="d-none mb-2" id="image-preview-container">
            <small class="fg-secondary d-block mb-1">New Attachment Preview:</small>
            <div class="bg-2 rounded border p-2" id="file-preview-box"></div>
        </div>

        <input
            class="form-control @error('attachment') is-invalid @enderror"
            id="attachment"
            name="attachment"
            type="file"
            accept="{{ UploadType::DOCUMENT_AND_IMAGES->accept() }}"
            onchange="previewSingleFile(event)"
        />
        <div class="form-text">{{ __('company_files.form.attachment_size_hint') }}</div>
        <div
            class="invalid-feedback d-none"
            id="attachment-size-error"
            data-too-large="{{ __('company_files.form.attachment_too_large') }}"
        ></div>

        @error('attachment')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    @else
        {{-- Create mode: multi-file upload with DataTransfer --}}
        <div class="row g-2 d-none mb-2" id="multi-file-preview-container"></div>

        <input
            class="form-control @error('attachments') is-invalid @enderror"
            id="attachments"
            name="attachments[]"
            type="file"
            multiple
            accept="{{ UploadType::DOCUMENT_AND_IMAGES->accept() }}"
            onchange="handleFileSelect(event)"
        />
        <div class="form-text">{{ __('company_files.form.attachment_size_hint') }}</div>
        <div
            class="invalid-feedback d-none"
            id="attachments-size-error"
            data-too-large="{{ __('company_files.form.attachment_too_large') }}"
        ></div>

        @error('attachments')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    @endif
</div>

<div class="mb-3">
    <label class="form-label fw-bold" for="notes">
        {{ __('company_files.form.label_notes') }}
    </label>
    <textarea
        class="form-control @error('notes') is-invalid @enderror"
        id="notes"
        name="notes"
        rows="3"
    >{{ old('notes', $companyFile?->notes) }}</textarea>
    @error('notes')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<script>
    @if ($companyFile)
        function previewSingleFile(event) {
            const input = event.target;
            const container = document.getElementById('image-preview-container');
            const previewBox = document.getElementById('file-preview-box');
            const errorBox = document.getElementById('attachment-size-error');
            errorBox.classList.add('d-none');
            errorBox.textContent = '';

            if (input.files && input.files[0]) {
                const file = input.files[0];

                if (file.size > 8000 * 1024) {
                    errorBox.textContent = errorBox.dataset.tooLarge + ' ' + file.name;
                    errorBox.classList.remove('d-none');
                    input.value = '';
                    container.classList.add('d-none');
                    previewBox.innerHTML = '';
                    return;
                }

                container.classList.remove('d-none');

                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function (e) {
                        previewBox.innerHTML = `<img src="${e.target.result}" class="rounded border img-thumbnail" style="max-height: 100px;" />`;
                    };
                    reader.readAsDataURL(file);
                } else {
                    previewBox.innerHTML = `<i class="me-2 fg-secondary" data-lucide="file-text"></i><span>${file.name}</span>`;
                }
            } else {
                container.classList.add('d-none');
                previewBox.innerHTML = '';
            }
        }
    @else
        // Store selected files in a DataTransfer container
        const fileDataStore = new DataTransfer();

        function handleFileSelect(event) {
            const input = event.target;
            const files = Array.from(input.files);
            const errorBox = document.getElementById('attachments-size-error');
            errorBox.classList.add('d-none');
            errorBox.textContent = '';

            const oversized = files.filter((file) => file.size > 8000 * 1024);
            const accepted = files.filter((file) => file.size <= 8000 * 1024);

            if (oversized.length > 0) {
                errorBox.textContent = errorBox.dataset.tooLarge + ' ' + oversized.map((file) => file.name).join(', ');
                errorBox.classList.remove('d-none');
            }

            // Append accepted files to the global DataTransfer store
            accepted.forEach((file) => {
                fileDataStore.items.add(file);
            });

            // Update the file input element's files
            input.files = fileDataStore.files;

            renderPreviews();
        }

        function removeFile(index) {
            // Remove item from DataTransfer at the specified index
            fileDataStore.items.remove(index);

            // Sync the updated list back to the file input
            const fileInput = document.getElementById('attachments');
            fileInput.files = fileDataStore.files;

            renderPreviews();
        }

        function renderPreviews() {
            const container = document.getElementById('multi-file-preview-container');
            container.innerHTML = '';

            const files = fileDataStore.files;

            if (files.length > 0) {
                container.classList.remove('d-none');

                Array.from(files).forEach((file, index) => {
                    const col = document.createElement('div');
                    col.className = 'md:col-3 col-6';

                    const card = document.createElement('div');
                    card.className = 'border rounded p-2 text-center bg-2 h-100 position-relative';

                    // Remove Button
                    const removeBtn = document.createElement('button');
                    removeBtn.type = 'button';
                    removeBtn.className =
                        'btn btn-solid theme-danger btn-sm position-absolute top-0 end-0 m-1 rounded-circle p-0 d-flex align-items-center justify-content-center';
                    removeBtn.style.width = '22px';
                    removeBtn.style.height = '22px';
                    removeBtn.innerHTML = '&times;';
                    removeBtn.onclick = () => removeFile(index);

                    card.appendChild(removeBtn);

                    if (file.type.startsWith('image/')) {
                        const reader = new FileReader();
                        reader.onload = function (e) {
                            const img = document.createElement('img');
                            img.src = e.target.result;
                            img.className = 'img-thumbnail mb-1';
                            img.style.maxHeight = '80px';

                            const name = document.createElement('small');
                            name.className = 'text-truncate d-block';
                            name.textContent = file.name;

                            card.appendChild(img);
                            card.appendChild(name);
                        };
                        reader.readAsDataURL(file);
                    } else {
                        const fileIcon = document.createElement('div');
                        fileIcon.className = 'py-2';
                        fileIcon.innerHTML = `<i class="fs-xl fg-secondary mb-1" data-lucide="file-text"></i><small class="text-truncate d-block">${file.name}</small>`;
                        card.appendChild(fileIcon);
                    }

                    col.appendChild(card);
                    container.appendChild(col);
                });
            } else {
                container.classList.add('d-none');
            }
        }
    @endif
</script>
