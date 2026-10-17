@if ($errors->any())
    <div class="alert alert theme-danger" role="alert">
        <strong class="d-block mb-1">
            <i class="me-1" data-lucide="triangle-alert"></i>
            {{ __('common.labels.system_error') }}
        </strong>
        <ul class="mb-0 ps-3">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button class="btn-close" data-bs-dismiss="alert" type="button" aria-label="Close"></button>
    </div>
@endif
