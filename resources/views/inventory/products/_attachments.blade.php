<div class="mb-4">
    <h6 class="fw-bold mb-1">{{ __('products.attachments.title') }}</h6>
    <p class="fg-secondary small mb-0">{{ __('products.attachments.hint') }}</p>
</div>

@if (isset($product) && $product->attachments->isNotEmpty())
    <div class="mb-4">
        <div class="form-label fw-bold">{{ __('products.attachments.existing') }}</div>
        <div class="row g-3">
            @foreach ($product->attachments as $attachment)
                <div class="col-6 md:col-4 xl:col-3">
                    <div class="card h-100 border">
                        <img
                            class="card-img-top object-fit-cover"
                            src="{{ route('inventory.products.attachments.download', [$product, $attachment]) }}"
                            alt="{{ $attachment->original_name }}"
                            style="height: 140px"
                        />
                        <div class="card-body p-2">
                            <div class="small text-truncate mb-2" title="{{ $attachment->original_name }}">
                                {{ $attachment->original_name }}
                            </div>
                            <input
                                class="check"
                                id="remove-attachment-{{ $attachment->id }}"
                                name="removed_attachment_ids[]"
                                type="checkbox"
                                value="{{ $attachment->id }}"
                                @checked(in_array($attachment->id, old('removed_attachment_ids', []), true))
                            />
                            <label class="fg-danger small"
                                for="remove-attachment-{{ $attachment->id }}"
                            >{{ __('products.attachments.remove') }}</label>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif

<div class="mb-3">
    <label class="form-label fw-bold" for="images"> {{ __('products.attachments.new') }} </label>
    <input
        class="form-control @error('images') is-invalid @enderror @error('images.*') is-invalid @enderror"
        id="images"
        name="images[]"
        type="file"
        accept="{{ \App\Enums\UploadType::IMAGES->accept() }}"
        multiple
        onchange="previewProductImages(event)"
    />
    @error('images')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
    @error('images.*')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

@error('removed_attachment_ids')
    <div class="alert alert theme-danger py-2">{{ $message }}</div>
@enderror
@error('removed_attachment_ids.*')
    <div class="alert alert theme-danger py-2">{{ $message }}</div>
@enderror

<div class="row g-3" id="attachment-preview"></div>

<div class="fg-secondary small mt-3">{{ __('products.attachments.shared_hint') }}</div>
