@php
    use App\Enums\FileAction;
    use App\Enums\FileResources;

    $canCreate = auth()->user()->hasFilePermission(FileResources::COMPANIES, FileAction::ADD);
    $canUpdate = auth()->user()->hasFilePermission(FileResources::COMPANIES, FileAction::UPDATE);
    $canRemove = auth()->user()->hasFilePermission(FileResources::COMPANIES, FileAction::REMOVE);
@endphp
@extends('layouts.app')

@section('content')
    <div class="card border-0 shadow-sm">
        <div class="card-header d-flex align-items-center justify-content-between bg-white py-3">
            <div>
                <h4 class="fw-bold mb-0">{{ __('companies.addresses.page_title') }}</h4>
                <p class="fg-secondary small mb-0">{{ __('companies.addresses.subtitle') }}</p>
            </div>
            @if ($canCreate)
                <a class="btn btn-solid theme-success btn-sm"
                    href="{{ route('crm.company-addresses.create-global', $companyId !== '' ? ['company' => $companyId] : []) }}">
                    <i class="me-1" data-lucide="plus"></i>
                    {{ __('companies.addresses.actions.add') }}
                </a>
            @endif
        </div>
        <div class="card-body">
            @include('partials.alerts')
            <form autocomplete="off" class="row g-2 mb-3" action="{{ route('crm.company-addresses.index') }}" method="GET">
                <div class="md:col-4">
                    <select class="form-control" name="company">
                        <option value="">{{ __('companies.addresses.all_companies') }}</option>
                        @foreach ($companies as $company)
                            <option value="{{ $company->id }}" @selected($companyId === $company->id)>
                                {{ $company->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="md:col-5">
                    <input class="form-control" name="search" value="{{ $search }}"
                        placeholder="{{ __('companies.addresses.search_placeholder') }}" />
                </div>
                <div class="md:col-3 d-flex gap-2">
                    <button class="btn btn-solid theme-secondary" type="submit">{{ __('common.actions.filter') }}</button>
                    <a class="btn btn-solid theme-secondary"
                        href="{{ route('crm.company-addresses.index') }}">{{ __('common.actions.reset') }}</a>
                </div>
            </form>

            <div class="table-responsive rounded border">
                <table class="table-hover table-striped mb-0 table align-middle">
                    <thead class="bg-1">
                        <tr>
                            <th>{{ __('companies.addresses.fields.company') }}</th>
                            <th>{{ __('companies.addresses.fields.name') }}</th>
                            <th>{{ __('companies.addresses.fields.phone') }}</th>
                            <th>{{ __('companies.addresses.fields.address') }}</th>
                            <th>{{ __('companies.addresses.fields.default') }}</th>
                            <th class="text-end">{{ __('common.labels.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($addresses as $address)
                            <tr>
                                <td>
                                    <a
                                        href="{{ route('crm.companies.show', $address->company) }}">{{ $address->company?->name ?? '-' }}</a>
                                </td>
                                <td class="fw-bold">{{ $address->name }}</td>
                                <td>{{ $address->phone ?? '-' }}</td>
                                <td>{!! nl2br(e($address->address)) !!}</td>
                                <td class="text-center">
                                    @if ($address->is_default)
                                        <span
                                            class="badge theme-success">{{ __('companies.addresses.fields.default') }}</span>
                                    @else
                                        <span class="text-center"><i data-lucide="minus-circle"></i></span>
                                    @endif
                                </td>
                                <td class="text-nowrap text-end">
                                    @if ($canUpdate)
                                        <a class="btn btn-sm btn-solid theme-warning me-1"
                                            href="{{ route('crm.company-addresses.edit', $address) }}"
                                            title="{{ __('common.actions.edit') }}">
                                            <i data-lucide="square-pen"></i>
                                        </a>
                                    @endif
                                    @if ($canRemove)
                                        <form autocomplete="off" class="d-inline"
                                            action="{{ route('crm.company-addresses.destroy', $address) }}" method="POST"
                                            data-confirm-message="{{ __('companies.addresses.messages.delete_confirm') }}"
                                            data-confirm-tone="danger">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-solid theme-danger" type="submit"
                                                title="{{ __('common.actions.delete') }}">
                                                <i data-lucide="trash-2"></i>
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="fg-secondary py-4 text-center" colspan="6">
                                    {{ __('companies.addresses.messages.empty') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-end mt-3">{{ $addresses->links() }}</div>
        </div>
    </div>
@endsection
