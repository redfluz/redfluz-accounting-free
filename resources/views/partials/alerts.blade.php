{{--
    Validation errors are rendered here as well as in partials/errors.

    Guards such as "this record is already used by other documents" throw a
    ValidationException from a controller on a listing page. Those listings
    include this partial but not partials/errors, so without the block below the
    warning was silently dropped and the user saw nothing happen.
--}}
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

@if (session('success'))
    <div class="alert alert theme-success" role="alert">
        <i class="me-2" data-lucide="circle-check"></i>
        {{ session('success') }}
        <button class="btn-close" data-bs-dismiss="alert" type="button" aria-label="Close" />
    </div>
@endif

@if (session('error'))
    <div class="alert alert theme-danger" role="alert">
        <i class="me-2" data-lucide="triangle-alert"></i>
        {{ session('error') }}
        <button class="btn-close" data-bs-dismiss="alert" type="button" aria-label="Close" />
    </div>
@endif

{{-- Informational notice, e.g. redirected off a page that no longer applies. --}}
@if (session('info'))
    <div class="alert alert theme-info" role="alert">
        <i class="me-2" data-lucide="info"></i>
        {{ session('info') }}
        <button class="btn-close" data-bs-dismiss="alert" type="button" aria-label="Close" />
    </div>
@endif
