@php
    use App\Enums\FileAction;
    use App\Enums\FileResources;

    $canUpdate = auth()->user()->hasFilePermission(FileResources::COMPANIES, FileAction::UPDATE);
@endphp

@extends('layouts.app')

@section('content')
    <div class="card md:col-8 mx-auto shadow-sm">
        <div class="card-header bg-white py-3">
            <h4 class="fw-bold m-0">{{ __('common.actions.edit') }}</h4>
        </div>

        <div class="card-body">
            @include('partials.errors')
            <form autocomplete="off"
                action="{{ route('crm.company-files.update', $companyFile) }}"
                method="POST"
                enctype="multipart/form-data"
            >
                @csrf
                @method('PUT')

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
                                {{ old('company_id', $companyFile->company_id) == $id ? 'selected' : '' }}
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
                    <label class="form-control form-adorn">
                        <input
                            class="form-ghost @error('title') is-invalid @enderror"
                            id="title"
                            name="title"
                            type="text"
                            value="{{ old('title', $companyFile->title) }}"
                            required
                        />
                    </label>
                    @error('title')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold" for="attachment">
                        {{ __('company_files.form.label_attachment') }}
                    </label>

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

                    <!-- New Selected File Preview Container -->
                    <div class="d-none mb-2" id="image-preview-container">
                        <small class="fg-secondary d-block mb-1">New Attachment Preview:</small>
                        <div class="bg-2 rounded border p-2" id="file-preview-box"></div>
                    </div>

                    <input
                        class="form-control @error('attachment') is-invalid @enderror"
                        id="attachment"
                        name="attachment"
                        type="file"
                        accept="{{ \App\Enums\UploadType::DOCUMENT_AND_IMAGES->accept() }}"
                        onchange="previewSingleFile(event)"
                    />

                    @error('attachment')
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
                    >{{ old('notes', $companyFile->notes) }}</textarea>
                    @error('notes')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-flex justify-content-between pt-2">
                    <a class="btn btn-solid theme-secondary" href="{{ route('crm.company-files.index') }}">
                        <i class="me-1" data-lucide="arrow-left"></i>
                        {{ __('common.actions.back') }}
                    </a>
                    @if ($canUpdate)
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
        function previewSingleFile(event) {
            const input = event.target;
            const container = document.getElementById('image-preview-container');
            const previewBox = document.getElementById('file-preview-box');

            if (input.files && input.files[0]) {
                const file = input.files[0];
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
    </script>
@endsection
