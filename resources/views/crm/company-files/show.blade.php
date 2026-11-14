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
                <div class="lg:col-5 md:col-6 d-flex flex-column gap-4">
                    <div class="card border shadow-sm">
                        <div class="card-header fw-bold border-bottom-0 bg-white px-3 py-2">
                            <i class="fg-secondary me-1" data-lucide="info"></i> {{ __('company_files.actions.show') }}
                        </div>
                        <div class="table-responsive">
                            <table class="table-hover table-striped mb-0 table align-middle">
                                <tbody>
                                    <tr>
                                        <th scope="row" class="bg-2 fg-secondary fw-semibold px-3" style="width: 35%">
                                            {{ __('company_files.table.column_company') }}
                                        </th>
                                        <td class="fw-bold fg-black px-3">{{ $companyFile->company->name ?? '-' }}</td>
                                    </tr>
                                    <tr>
                                        <th scope="row" class="bg-2 fg-secondary fw-semibold px-3">
                                            {{ __('company_files.table.column_title') }}
                                        </th>
                                        <td class="px-3">{{ $companyFile->title }}</td>
                                    </tr>
                                    <tr>
                                        <th scope="row" class="bg-2 fg-secondary fw-semibold px-3">
                                            {{ __('company_files.table.column_date') }}
                                        </th>
                                        <td class="px-3">{{ $companyFile->created_at->format(Setting::getDefaultDate('long_date_format')) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="card flex-grow-1 border shadow-sm">
                        <div class="card-header fw-bold bg-white px-3 py-2">
                            <i class="fg-secondary me-1" data-lucide="sticky-note"></i>
                            {{ __('company_files.table.column_notes') }}
                        </div>
                        <div class="card-body bg-subtle-secondary">
                            <div class="fg-secondary small lh-lg">
                                {!! nl2br(e($companyFile->notes ?? '-')) !!}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="lg:col-7 md:col-6">
                    <div class="card h-100 border shadow-sm">
                        <div class="card-header fw-bold border-bottom-0 bg-white px-3 pt-3">
                            <i class="fg-secondary me-1" data-lucide="paperclip"></i>
                            {{ __('company_files.tabs.attachments') }}
                        </div>
                        <div class="card-body">
                            @php
                                $ext = strtolower(pathinfo($companyFile->file_name ?? $companyFile->file_path ?? '', PATHINFO_EXTENSION));
                                $isImage = str_starts_with($companyFile->mime_type ?? '', 'image/') || in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true);
                            @endphp
                            @if ($companyFile->file_path)
                                <div class="row g-3">
                                    <div class="col-6 md:col-4">
                                        @if ($isImage)
                                            <a href="{{ route('crm.company-files.attachment', $companyFile) }}"
                                               data-fancybox="company-file-attachments"
                                               data-caption="{{ $companyFile->file_name ?: $companyFile->title }}">
                                                <img class="img-thumbnail object-fit-cover w-100"
                                                     src="{{ route('crm.company-files.attachment', $companyFile) }}"
                                                     alt="{{ $companyFile->file_name ?: $companyFile->title }}"
                                                     style="height: 150px" />
                                            </a>
                                            <div class="small text-truncate mt-1" title="{{ $companyFile->file_name }}">{{ $companyFile->file_name }}</div>
                                        @else
                                            <a class="border rounded bg-2 d-flex flex-column align-items-center justify-content-center text-decoration-none p-3 h-100"
                                               href="{{ route('crm.company-files.attachment', $companyFile) }}"
                                               target="_blank"
                                               style="min-height: 150px">
                                                <i class="fs-xl fg-secondary mb-2" data-lucide="file-text"></i>
                                                <small class="text-truncate d-block w-100 text-center" title="{{ $companyFile->file_name }}">{{ $companyFile->file_name ?: '-' }}</small>
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            @else
                                <div class="fg-secondary py-4 text-center">{{ __('company_files.messages.no_attachment') }}</div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script type="module">
        import { Fancybox } from '@fancyapps/ui';

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