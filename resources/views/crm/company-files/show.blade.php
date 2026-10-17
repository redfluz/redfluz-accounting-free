@php use App\Models\System\Setting; @endphp
@extends('layouts.app')

@section('content')
    <div class="card shadow-sm">
        {{-- Header --}}
        <div class="card-header d-flex align-items-center justify-content-between bg-white py-3">
            <h4 class="fw-bold m-0">
                <i class="fg-primary me-2" data-lucide="file-text"></i>
                {{ __('company_files.actions.show') }}
            </h4>
            <div class="d-flex gap-2">
                <a class="btn btn-solid theme-warning btn-sm" href="{{ route('crm.company-files.edit', $companyFile) }}">
                    <i class="me-1" data-lucide="square-pen"></i>
                    {{ __('common.actions.edit') }}
                </a>
                <a class="btn btn-solid theme-secondary btn-sm" href="{{ route('crm.company-files.index') }}">
                    <i class="me-1" data-lucide="arrow-left"></i>
                    {{ __('common.actions.back') }}
                </a>
            </div>
        </div>

        <div class="card-body p-4">
            @include('partials.alerts')

            <div class="row g-4">
                {{-- LEFT COLUMN: Attachment Preview --}}
                <div class="lg:col-5 md:col-6">
                    <div class="card bg-2 h-100 border shadow-sm">
                        <div
                            class="card-header fw-bold d-flex align-items-center justify-content-between border-bottom-0 bg-white px-3 pt-3">
                            <span>
                                <i class="fg-secondary me-1" data-lucide="paperclip"></i>
                                {{ __('company_files.table.column_attachment') }}
                            </span>
                            @if ($companyFile->file_path)
                                <a class="btn btn-sm btn-solid theme-primary px-2 py-0"
                                    href="{{ route('crm.company-files.attachment', $companyFile) }}" download>
                                    <i class="me-1" data-lucide="download"></i>
                                    {{ __('company_files.actions.download') }}
                                </a>
                            @endif
                        </div>

                        <div class="card-body d-flex flex-column align-items-center justify-content-center p-3"
                           >
                            @if ($companyFile->file_path && str_starts_with($companyFile->mime_type ?? '', 'image/'))
                                <a class="preview-image-wrapper w-100 position-relative d-block overflow-hidden rounded border bg-white text-center shadow-sm"
                                    data-fancybox="gallery" data-caption="{{ $companyFile->title }}"
                                    href="{{ route('crm.company-files.attachment', $companyFile) }}">
                                    <img class="img-fluid preview-img rounded"
                                        src="{{ route('crm.company-files.attachment', $companyFile) }}"
                                        alt="{{ $companyFile->title }}" />

                                    <div class="hover-overlay d-flex align-items-center justify-content-center">
                                        <span class="btn btn-outline btn-sm shadow-sm">
                                            <i class="me-1" data-lucide="zoom-in"></i> Zoom Preview
                                        </span>
                                    </div>
                                </a>
                            @else
                                <div class="fg-secondary py-5 text-center">
                                    <i class="fs-4xl mb-2 opacity-50" data-lucide="file-x"></i>
                                    <p class="small mb-0">{{ __('company_files.messages.no_attachment') }}</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- RIGHT COLUMN: Attributes & Notes --}}
                <div class="lg:col-7 md:col-6 d-flex flex-column gap-4">
                    {{-- TOP: Attributes Table --}}
                    <div class="card border shadow-sm">
                        <div class="card-header fw-bold border-bottom-0 bg-white px-3 py-2">
                            <i class="fg-secondary me-1" data-lucide="info"></i> File Details
                        </div>
                        <div class="table-responsive">
                            <table class="table-form table-hover table-striped mb-0 table align-middle">
                                <tbody>
                                    <tr>
                                        <th class="bg-2 fg-secondary fw-semibold px-3" style="width: 35%">
                                            {{ __('company_files.table.column_company') }}
                                        </th>
                                        <td class="fw-bold fg-black px-3">{{ $companyFile->company->name ?? '-' }}</td>
                                    </tr>
                                    <tr>
                                        <th class="bg-2 fg-secondary fw-semibold px-3">
                                            {{ __('company_files.table.column_title') }}
                                        </th>
                                        <td class="px-3">{{ $companyFile->title }}</td>
                                    </tr>
                                    <tr>
                                        <th class="bg-2 fg-secondary fw-semibold px-3">
                                            {{ __('company_files.table.column_file_name') }}
                                        </th>
                                        <td class="text-break px-3">{{ $companyFile->file_name ?? '-' }}</td>
                                    </tr>
                                    <tr>
                                        <th class="bg-2 fg-secondary fw-semibold px-3">
                                            {{ __('company_files.table.column_mime_type') }}
                                        </th>
                                        <td class="px-3">
                                            <span class="badge theme-secondary font-monospace">
                                                {{ $companyFile->mime_type ?? '-' }}
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th class="bg-2 fg-secondary fw-semibold px-3">
                                            {{ __('company_files.table.column_file_size') }}
                                        </th>
                                        <td class="px-3">
                                            @if ($companyFile->file_size)
                                                {{ number_format($companyFile->file_size / 1024, 2) }} KB
                                            @else
                                                -
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <th class="bg-2 fg-secondary fw-semibold px-3">
                                            {{ __('company_files.table.column_date') }}
                                        </th>
                                        <td class="px-3">{{ $companyFile->created_at->format(Setting::getDefaultDate('long_date_format')) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- BOTTOM RIGHT: Notes Section --}}
                    <div class="card flex-grow-1 border shadow-sm">
                        <div class="card-header fw-bold bg-white px-3 py-2">
                            <i class="fg-secondary me-1" data-lucide="sticky-note"></i>
                            {{ __('company_files.table.column_notes') }}
                        </div>
                        <div class="card-body bg-subtle-secondary"
                           >
                            <div class="fg-secondary small lh-lg">
                                {!! nl2br(e($companyFile->notes ?? __('company_files.messages.no_attachment'))) !!}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .preview-image-wrapper {
            max-height: 420px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .preview-img {
            max-height: 400px;
            width: auto;
            object-fit: contain;
            transition: transform 0.3s ease;
        }

        .hover-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.35);
            opacity: 0;
            transition: opacity 0.25s ease;
        }

        .preview-image-wrapper:hover .hover-overlay {
            opacity: 1;
        }

        .preview-image-wrapper:hover .preview-img {
            transform: scale(1.03);
        }
    </style>
@endpush

@push('scripts')
    <script type="module">
        import {
            Fancybox
        } from '@fancyapps/ui';

        Fancybox.bind('[data-fancybox]', {
            Hash: false,
            Thumbs: false,
            Images: {
                Panzoom: {
                    maxScale: 3,
                },
            },
        });
    </script>
@endpush
