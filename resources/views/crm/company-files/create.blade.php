@php
    use App\Enums\FileAction;
    use App\Enums\FileResources;

    $canCreate = auth()->user()->hasFilePermission(FileResources::COMPANIES, FileAction::ADD);
@endphp

@extends('layouts.app')

@section('content')
    <div class="card md:col-8 mx-auto shadow-sm">
        <div class="card-header bg-white py-3">
            <h4 class="fw-bold m-0">{{ __('common.actions.add') }}</h4>
        </div>

        <div class="card-body">
            @include('partials.errors')
            <form autocomplete="off"
                id="company-files-form"
                action="{{ route('crm.company-files.store') }}"
                method="POST"
                enctype="multipart/form-data"
            >
                @csrf

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
                            <option value="{{ $id }}" {{ old('company_id') == $id ? 'selected' : '' }}>
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
                    <label class="form-control form-adorn">
                        <input
                            class="form-ghost @error('title') is-invalid @enderror"
                            id="title"
                            name="title"
                            type="text"
                            value="{{ old('title') }}"
                            required
                        />
                    </label>
                    @error('title')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold" for="attachments">
                        {{ __('company_files.form.label_attachment') }}
                    </label>

                    <!-- Dynamic File Preview Container -->
                    <div class="row g-2 d-none mb-2" id="multi-file-preview-container"></div>

                    <input
                        class="form-control @error('attachments') is-invalid @enderror"
                        id="attachments"
                        name="attachments[]"
                        type="file"
                        multiple
                        accept="{{ \App\Enums\UploadType::DOCUMENT_AND_IMAGES->accept() }}"
                        onchange="handleFileSelect(event)"
                    />

                    @error('attachments')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
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
                    >{{ old('notes') }}</textarea>
                    @error('notes')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-flex justify-content-between pt-2">
                    <a class="btn btn-solid theme-secondary" href="{{ route('crm.company-files.index') }}">
                        <i class="me-1" data-lucide="arrow-left"></i>
                        {{ __('common.actions.back') }}
                    </a>
                    @if ($canCreate)
                        <button class="btn btn-solid theme-accent" type="submit">
                            <i class="me-1" data-lucide="save"></i>
                            {{ __('common.actions.save') }}
                        </button>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <script>
        // Store selected files in a DataTransfer container
        const fileDataStore = new DataTransfer();

        function handleFileSelect(event) {
            const input = event.target;
            const files = Array.from(input.files);

            // Append newly picked files to the global DataTransfer store
            files.forEach((file) => {
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
    </script>
@endsection
