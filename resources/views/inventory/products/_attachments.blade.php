@php use App\Enums\UploadType; @endphp
<div class="mb-4">
    <h6 class="fw-bold mb-1">{{ __('products.attachments.title') }}</h6>
    <p class="fg-secondary small mb-0">{{ __('products.attachments.hint') }}</p>
</div>

@if (isset($product) && $product->attachments->isNotEmpty())
    <div class="mb-4">
        <div class="form-label fw-bold mb-2">{{ __('products.attachments.existing') }}</div>
        <div class="row g-2">
            @foreach ($product->attachments as $attachment)
                @php $isRemoving = in_array($attachment->id, old('removed_attachment_ids', []), true); @endphp
                <div class="col-6 col-md-4 col-xl-3">
                    <div class="position-relative border rounded overflow-hidden h-100 {{ $isRemoving ? 'opacity-50' : '' }}"
                         id="existing-attachment-card-{{ $attachment->id }}">
                        <img
                            class="w-100 d-block"
                            src="{{ route('inventory.products.attachments.download', [$product, $attachment]) }}"
                            alt="{{ $attachment->original_name }}"
                            style="height: 140px; object-fit: cover;"
                        />
                        <button
                            type="button"
                            class="btn btn-sm position-absolute top-0 end-0 m-1 {{ $isRemoving ? 'btn-solid theme-secondary' : 'btn-solid theme-danger' }}"
                            id="existing-attachment-toggle-{{ $attachment->id }}"
                            onclick="toggleExistingProductAttachment('{{ $attachment->id }}')"
                            title="{{ __('products.attachments.remove') }}"
                            aria-label="{{ __('products.attachments.remove') }}"
                        >
                            <span class="remove-label {{ $isRemoving ? 'd-none' : '' }}">&times;</span>
                            <span class="undo-label {{ $isRemoving ? '' : 'd-none' }}">{{ __('common.actions.undo') }}</span>
                        </button>
                        <div class="p-2 border-top bg-white">
                            <div class="small text-truncate" title="{{ $attachment->original_name }}">
                                {{ $attachment->original_name }}
                            </div>
                            <div class="small fg-danger mt-1 {{ $isRemoving ? '' : 'd-none' }}"
                                 id="existing-attachment-status-{{ $attachment->id }}">
                                {{ __('products.attachments.remove') }}
                            </div>
                        </div>
                        <input
                            class="d-none"
                            id="remove-attachment-{{ $attachment->id }}"
                            name="removed_attachment_ids[]"
                            type="checkbox"
                            value="{{ $attachment->id }}"
                            @checked($isRemoving)
                        />
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif

<div class="mb-3">
    <div class="form-label fw-bold mb-2">{{ __('products.attachments.new') }}</div>

    @error('images')
    <div class="alert alert theme-danger py-2 mb-2">{{ $message }}</div>
    @enderror
    @error('images.*')
    <div class="alert alert theme-danger py-2 mb-2">{{ $message }}</div>
    @enderror

    <div class="row g-2">
        @for ($i = 0; $i < 10; $i++)
            <div class="col-6 col-md-4 col-xl-3">
                <div class="border rounded overflow-hidden h-100 position-relative" id="image-slot-card-{{ $i }}">
                    <div
                        class="d-flex flex-column align-items-center justify-content-center text-center p-3"
                        id="image-slot-empty-{{ $i }}"
                        style="min-height: 170px; cursor: pointer;"
                        onclick="document.getElementById('image-slot-{{ $i }}').click()"
                    >
                        <div class="fs-3 fg-secondary mb-1">+</div>
                        <div class="small fg-secondary">{{ __('products.attachments.new') }}</div>
                    </div>

                    <div class="d-none h-100" id="image-slot-preview-wrap-{{ $i }}">
                        <img
                            id="image-slot-preview-{{ $i }}"
                            class="w-100 d-block"
                            style="height: 140px; object-fit: cover;"
                            alt=""
                        />
                        <button
                            type="button"
                            class="btn btn-sm btn-solid theme-danger position-absolute top-0 end-0 m-1"
                            onclick="clearProductImageSlot({{ $i }})"
                            title="{{ __('products.attachments.remove') }}"
                            aria-label="{{ __('products.attachments.remove') }}"
                        >&times;</button>
                        <div class="p-2 border-top bg-white">
                            <div class="small text-truncate" id="image-slot-name-{{ $i }}"></div>
                        </div>
                    </div>

                    <input
                        class="d-none"
                        id="image-slot-{{ $i }}"
                        name="images[]"
                        type="file"
                        accept="{{ UploadType::IMAGES->accept() }}"
                        onchange="previewProductImageSlot(this, {{ $i }})"
                    />
                    <div class="text-danger small d-none p-2 pt-0" id="image-slot-error-{{ $i }}"
                         data-too-large="{{ __('products.attachments.too_large') }}"></div>
                </div>
            </div>
        @endfor
    </div>
    <div class="alert alert theme-danger py-2 mt-2 d-none" id="images-submit-error"></div>
</div>

@error('removed_attachment_ids')
<div class="alert alert theme-danger py-2 mt-2">{{ $message }}</div>
@enderror
@error('removed_attachment_ids.*')
<div class="alert alert theme-danger py-2 mt-2">{{ $message }}</div>
@enderror

<script>
    (function () {
        const productForm = document.getElementById('product-form');
        if (!productForm) return;

        productForm.addEventListener('submit', function (event) {
            const errorBox = document.getElementById('images-submit-error');
            if (errorBox) {
                errorBox.classList.add('d-none');
                errorBox.textContent = '';
            }

            const existingCount = {{ isset($product) ? $product->attachments->count() : 0 }};
            const removedCount = document.querySelectorAll('input[name="removed_attachment_ids[]"]:checked').length;

            let newCount = 0;
            let totalBytes = 0;
            const oversized = [];
            const maxPerFile = 8000 * 1024;

            for (let i = 0; i < 10; i++) {
                const input = document.getElementById('image-slot-' + i);
                if (!input || !input.files || !input.files[0]) continue;
                const file = input.files[0];
                newCount++;
                totalBytes += file.size;
                if (file.size > maxPerFile) oversized.push(file.name);
            }

            let message = '';
            if (oversized.length > 0) {
                message = @json(__('products.attachments.too_large')) + ' ' + oversized.join(', ');
            } else if (existingCount - removedCount + newCount > 10) {
                message = @json(__('products.validation.maximum_attachments', ['max' => 10]));
            } else if (totalBytes > maxPerFile * 10) {
                message = @json(__('products.attachments.too_large'));
            }

            if (message) {
                event.preventDefault();
                if (errorBox) {
                    errorBox.textContent = message;
                    errorBox.classList.remove('d-none');
                }
                const tab = document.querySelector('[data-product-tab="attachments"]');
                if (tab) tab.click();
                errorBox?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });
    })();

    function toggleExistingProductAttachment(id) {
        const checkbox = document.getElementById('remove-attachment-' + id);
        const card = document.getElementById('existing-attachment-card-' + id);
        const toggle = document.getElementById('existing-attachment-toggle-' + id);
        const status = document.getElementById('existing-attachment-status-' + id);
        if (!checkbox || !card) return;

        checkbox.checked = !checkbox.checked;
        const removing = checkbox.checked;

        card.classList.toggle('opacity-50', removing);
        if (status) status.classList.toggle('d-none', !removing);
        if (toggle) {
            toggle.classList.toggle('theme-danger', !removing);
            toggle.classList.toggle('theme-secondary', removing);
            const removeLabel = toggle.querySelector('.remove-label');
            const undoLabel = toggle.querySelector('.undo-label');
            if (removeLabel) removeLabel.classList.toggle('d-none', removing);
            if (undoLabel) undoLabel.classList.toggle('d-none', !removing);
        }
    }

    function previewProductImageSlot(input, index) {
        const errorBox = document.getElementById('image-slot-error-' + index);
        const empty = document.getElementById('image-slot-empty-' + index);
        const wrap = document.getElementById('image-slot-preview-wrap-' + index);
        const preview = document.getElementById('image-slot-preview-' + index);
        const name = document.getElementById('image-slot-name-' + index);

        if (errorBox) {
            errorBox.classList.add('d-none');
            errorBox.classList.remove('d-block');
            errorBox.textContent = '';
        }

        const file = input.files && input.files[0];

        if (!file) {
            if (wrap) wrap.classList.add('d-none');
            if (empty) empty.classList.remove('d-none');
            if (preview) preview.removeAttribute('src');
            if (name) name.textContent = '';
            return;
        }

        if (file.size > 8000 * 1024) {
            const message = (errorBox?.dataset.tooLarge || 'File too large:') + ' ' + file.name;
            if (errorBox) {
                errorBox.textContent = message;
                errorBox.classList.remove('d-none');
                errorBox.classList.add('d-block');
            }
            input.value = '';
            if (wrap) wrap.classList.add('d-none');
            if (empty) empty.classList.remove('d-none');
            if (preview) preview.removeAttribute('src');
            if (name) name.textContent = '';
            window.alert(message);
            return;
        }

        const reader = new FileReader();
        reader.addEventListener('load', (loadEvent) => {
            if (preview) preview.src = loadEvent.target.result;
        });
        reader.readAsDataURL(file);

        if (name) {
            name.textContent = file.name;
            name.title = file.name;
        }
        if (empty) empty.classList.add('d-none');
        if (wrap) wrap.classList.remove('d-none');
    }

    function clearProductImageSlot(index) {
        const input = document.getElementById('image-slot-' + index);
        const errorBox = document.getElementById('image-slot-error-' + index);
        const empty = document.getElementById('image-slot-empty-' + index);
        const wrap = document.getElementById('image-slot-preview-wrap-' + index);
        const preview = document.getElementById('image-slot-preview-' + index);
        const name = document.getElementById('image-slot-name-' + index);

        if (input) input.value = '';
        if (errorBox) {
            errorBox.classList.add('d-none');
            errorBox.classList.remove('d-block');
            errorBox.textContent = '';
        }
        if (wrap) wrap.classList.add('d-none');
        if (empty) empty.classList.remove('d-none');
        if (preview) preview.removeAttribute('src');
        if (name) name.textContent = '';
    }
</script>

<div class="fg-secondary small mt-3">{{ __('products.attachments.shared_hint') }}</div>
