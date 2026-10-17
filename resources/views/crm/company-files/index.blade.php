@php
    use App\Models\System\Setting;
    use App\Enums\FileAction;
    use App\Enums\FileResources;
    use App\Models\Crm\CompanyFile;

    $canCreate = auth()->user()->hasFilePermission(FileResources::COMPANIES, FileAction::ADD);
    $canUpdate = auth()->user()->hasFilePermission(FileResources::COMPANIES, FileAction::UPDATE);
    $canRemove = auth()->user()->hasFilePermission(FileResources::COMPANIES, FileAction::REMOVE);
@endphp
@props([
    /** @var CompanyFile */
    'companyFile',
])
@extends('layouts.app')

@section('content')
    <div class="card border-0 shadow-sm">
        <div class="card-header d-flex align-items-center justify-content-between bg-white py-3">
            <h4 class="fw-bold m-0">
                {{ __('company_files.title') }}
                <p class="fg-secondary small mb-0">{{ __('company_files.subtitle') }}</p>
            </h4>
            @if ($canCreate)
                <a class="btn btn-solid theme-success btn-sm" href="{{ route('crm.company-files.create') }}">
                    <i class="me-1" data-lucide="plus"></i>
                    {{ __('common.actions.add') }}
                </a>
            @endif
        </div>

        <div class="card-body">
            @include('partials.alerts')

            <!-- Search Bar -->
            <form autocomplete="off" class="row g-2 mb-3" method="GET" action="{{ route('crm.company-files.index') }}">
                <div class="md:col-4">
                    <div class="input-group">
                        <input class="form-control" name="search" type="text" value="{{ $search }}"
                            placeholder="{{ __('common.actions.search') }}" />
                        <button class="btn btn-solid theme-secondary" type="submit">
                            <i class="" data-lucide="search"></i>
                        </button>
                        @if ($search)
                            <a class="btn btn-solid theme-danger" href="{{ route('crm.company-files.index') }}"
                                title="{{ __('common.actions.reset') }}">
                                <i class="" data-lucide="x"></i>
                            </a>
                        @endif
                    </div>
                </div>
            </form>

            <div class="table-responsive rounded-5 overflow-hidden border">
                <table class="table-hover table-striped mb-0 table align-middle">
                    <thead class="bg-1">
                        <tr>
                            <th>{{ __('company_files.table.column_company') }}</th>
                            <th>{{ __('company_files.table.column_title') }}</th>
                            <th>{{ __('company_files.table.column_attachment') }}</th>
                            <th>{{ __('company_files.table.column_date') }}</th>
                            <th class="col-action text-center">{{ __('common.labels.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($companyFiles as $companyFile)
                            <tr>
                                <td>
                                    <span class="fw-bold"> {{ $companyFile->company->name ?? '-' }} </span>
                                </td>
                                <td>
                                    <a class="text-decoration-none fw-bold"
                                        href="{{ route('crm.company-files.show', $companyFile) }}">
                                        {{ $companyFile->title }}
                                    </a>
                                </td>
                                <td>
                                    @if ($companyFile->file_path)
                                        @if (str_starts_with($companyFile->mime_type ?? '', 'image/'))
                                            <a data-fancybox="gallery" data-caption="{{ $companyFile->title }}"
                                                href="{{ route('crm.company-files.attachment', $companyFile) }}">
                                                <img class="img-thumbnail me-1 rounded border"
                                                    src="{{ route('crm.company-files.attachment', $companyFile) }}"
                                                    alt="{{ $companyFile->title }}"
                                                    style="max-height: 40px; cursor: pointer" />
                                            </a>
                                        @else
                                            <a class="badge theme-secondary text-decoration-none"
                                                href="{{ route('crm.company-files.attachment', $companyFile) }}"
                                                target="_blank">
                                                <i class="me-1" data-lucide="paperclip"></i>
                                                {{ $companyFile->file_name }}
                                            </a>
                                        @endif
                                    @else
                                        <span class="fg-secondary small">-</span>
                                    @endif
                                </td>
                                <td>
                                    <small
                                        class="badge theme-secondary">{{ $companyFile->created_at->format(Setting::getDefaultDate()) }}</small>
                                </td>
                                <td class="text-center">
                                    <a class="btn btn-sm btn-solid theme-info me-1"
                                        href="{{ route('crm.company-files.show', $companyFile) }}"
                                        title="{{ __('common.actions.view') }}">
                                        <i data-lucide="eye"></i>
                                    </a>
                                    @if ($canUpdate)
                                        <a class="btn btn-sm btn-solid theme-warning me-1"
                                            href="{{ route('crm.company-files.edit', $companyFile) }}"
                                            title="{{ __('common.actions.edit') }}">
                                            <i data-lucide="square-pen"></i>
                                        </a>
                                    @endif
                                    @if ($canRemove)
                                        <button class="btn btn-sm btn-solid theme-danger btn-delete"
                                            data-id="{{ $companyFile->id }}" type="button"
                                            title="{{ __('common.actions.delete') }}">
                                            <i data-lucide="trash-2"></i>
                                        </button>

                                        <form autocomplete="off" class="d-none" id="delete-form-{{ $companyFile->id }}"
                                            action="{{ route('crm.company-files.destroy', $companyFile) }}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="fg-secondary py-4 text-center" colspan="6">
                                    {{ __('common.labels.not_found_record') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-end mt-3">{{ $companyFiles->links() }}</div>
        </div>
    </div>
@endsection

@section('script')
    <script type="module">
        document.addEventListener('click', function(e) {
            const deleteBtn = e.target.closest('.btn-delete');

            if (deleteBtn) {
                const dataId = deleteBtn.getAttribute('data-id');

                confirmDialog({
                    title: "{{ __('common.alerts.confirm_delete') }}",
                    text: "{{ __('common.alerts.confirm_delete_message') }}",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: "{{ __('common.alerts.confirm_delete_yes') }}",
                    confirmTone: 'danger',
                }).then((result) => {
                    if (result.isConfirmed) {
                        document.getElementById('delete-form-' + dataId).submit();
                    }
                });
            }
        });
    </script>
@endsection
