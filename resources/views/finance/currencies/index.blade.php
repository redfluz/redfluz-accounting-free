@php
    use App\Enums\FileAction;
    use App\Enums\FileResources;
    use App\Models\Finance\Currency;

@endphp
@extends('layouts.app')

@section('content')
    <div class="card border-0 shadow-sm">
        <div class="card-header d-flex align-items-center justify-content-between bg-white py-3">
            <h4 class="fw-bold m-0">
                {{ __('currencies.title') }}
                <p class="fg-secondary small mb-0">{{ __('currencies.subtitle') }}</p>
            </h4>
        </div>

        <div class="card-body">
            @include('partials.alerts')
            <form autocomplete="off" class="row g-2 mb-3" method="GET" action="{{ route('finance.currencies.index') }}">
                <div class="md:col-4">
                    <div class="input-group">
                        <input class="form-control" name="search" type="text" value="{{ $search }}"
                            placeholder="{{ __('common.actions.search') }}" />
                        <button class="btn btn-solid theme-secondary" type="submit">
                            <i class="" data-lucide="search"></i>
                        </button>
                        @if ($search)
                            <a class="btn btn-solid theme-danger" href="{{ route('finance.currencies.index') }}"
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
                            <th>{{ __('currencies.table.column_code') }}</th>
                            <th>{{ __('currencies.table.column_name') }}</th>
                            <th>{{ __('currencies.table.column_symbol') }}</th>
                            <th>{{ __('currencies.table.column_default') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($currencies as $currency)
                            <tr>
                                <td class="fw-bold"><span class="badge theme-secondary">{{ $currency->code }}</span></td>
                                <td>{{ $currency->name }}</td>
                                <td>{{ $currency->symbol }}</td>
                                <td>
                                    @if ($currency->is_default)
                                        <span class="badge theme-success">{{ __('common.labels.yes') }}</span>
                                    @else
                                        <span class="badge theme-secondary">{{ __('common.labels.no') }}</span>
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
            <div class="d-flex justify-content-end mt-3">{{ $currencies->links() }}</div>
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
