@php
    use App\Enums\FileAction;
    use App\Enums\FileResources;
    use App\Models\Crm\Contact;

    $canCreate = auth()->user()->hasFilePermission(FileResources::CONTACTS, FileAction::ADD);
    $canUpdate = auth()->user()->hasFilePermission(FileResources::CONTACTS, FileAction::UPDATE);
    $canRemove = auth()->user()->hasFilePermission(FileResources::CONTACTS, FileAction::REMOVE);
@endphp
@props([
    /** @var Contact */
    'contact',
])
@extends('layouts.app')

@section('content')
    <div class="card border-0 shadow-sm">
        <div class="card-header d-flex align-items-center justify-content-between bg-white py-3">
            <h4 class="fw-bold m-0">
                {{ __('contacts.title') }}
                <p class="fg-secondary small mb-0">{{ __('contacts.subtitle') }}</p>
            </h4>
            @if ($canCreate)
                <a class="btn btn-solid theme-success btn-sm" href="{{ route('crm.contacts.create') }}">
                    <i class="me-1" data-lucide="plus"></i>
                    {{ __('common.actions.add') }}
                </a>
            @endif
        </div>

        <div class="card-body">
            @include('partials.alerts')
            <!-- Character Filter Bar -->
            <div class="d-flex align-items-center mb-3 flex-wrap gap-1">
                <a
                    class="btn btn-sm {{ empty($character) ? 'btn-solid theme-primary' : 'btn-solid theme-secondary' }}"
                    href="{{ route('crm.contacts.index', array_filter(['search' => $search])) }}"
                >
                    {{ __('common.labels.all') ?? 'All' }}
                </a>

                @foreach ($alphabet as $char)
                    <a
                        class="btn btn-sm {{ $character === $char ? 'btn-solid theme-primary' : 'btn-solid theme-secondary' }}"
                        href="{{ route('crm.contacts.index', array_filter(['character' => $char, 'search' => $search])) }}"
                    >
                        {{ $char }}
                    </a>
                @endforeach
            </div>
            <!-- Search Bar -->
            <form autocomplete="off" class="row g-2 mb-3" method="GET" action="{{ route('crm.contacts.index') }}">
                <div class="md:col-4">
                    <div class="input-group">
                        <input
                            class="form-control"
                            name="search"
                            type="text"
                            value="{{ $search }}"
                            placeholder="{{ __('common.actions.search') }}"
                        />
                        <button class="btn btn-solid theme-secondary" type="submit">
                            <i class="" data-lucide="search"></i>
                        </button>
                        @if ($search)
                            <a
                                class="btn btn-solid theme-danger"
                                href="{{ route('crm.contacts.index') }}"
                                title="{{ __('common.actions.reset') }}"
                            >
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
                            <th>{{ __('contacts.table.column_name') }}</th>
                            <th>{{ __('contacts.table.column_company') }}</th>
                            <th>{{ __('contacts.table.column_email') }}</th>
                            <th>{{ __('contacts.table.column_phone') }}</th>
                            <th class="col-action text-center">{{ __('common.labels.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($contacts as $contact)
                            <tr>
                                <td class="fw-bold">{{ $contact->name }}</td>
                                <td>{{ $contact->company->name }}</td>
                                <td>{{ $contact->email ?? '-' }}</td>
                                <td>{{ $contact->phone ?? '-' }}</td>
                                <td class="text-center">
                                    @if ($canUpdate)
                                        <a
                                            class="btn btn-sm btn-solid theme-warning me-1"
                                            href="{{ route('crm.contacts.edit', $contact) }}"
                                            title="{{ __('common.actions.edit') }}"
                                        >
                                            <i data-lucide="square-pen"></i>
                                        </a>
                                    @endif
                                    @if ($canRemove)
                                        <button
                                            class="btn btn-sm btn-solid theme-danger btn-delete"
                                            data-id="{{ $contact->id }}"
                                            type="button"
                                            title="{{ __('common.actions.delete') }}"
                                        >
                                            <i data-lucide="trash-2"></i>
                                        </button>

                                        <form autocomplete="off"
                                            class="d-none"
                                            id="delete-form-{{ $contact->id }}"
                                            action="{{ route('crm.contacts.destroy', $contact) }}"
                                            method="POST"
                                        >
                                            @csrf
                                            @method('DELETE')
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="fg-secondary py-4 text-center" colspan="7">
                                    {{ __('common.labels.not_found_record') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-end mt-3">{{ $contacts->links() }}</div>
        </div>
    </div>
@endsection

@section('script')
    <script type="module">
        document.addEventListener('click', function (e) {
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
