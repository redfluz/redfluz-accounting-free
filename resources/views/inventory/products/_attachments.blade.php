@php
    use App\Enums\UploadType;

    $imageExtensions = ['jpg', 'jpeg', 'png', 'webp'];
@endphp

<div class="mb-4">
    <h6 class="fw-bold mb-1">{{ __('products.attachments.title') }}</h6>
    <p class="fg-secondary small mb-0">{{ __('products.attachments.hint') }}</p>
</div>

@if (isset($product) && $product->attachments->isNotEmpty())
    <div class="mb-4">
        <div class="form-label fw-bold mb-2">{{ __('products.attachments.existing') }}</div>
        <div class="row g-2">
            @foreach ($product->attachments as $attachment)
                @php
                    $isRemoving = in_array($attachment->id, old('removed_attachment_ids', []), true);
                    $ext = strtolower(pathinfo($attachment->original_name ?? $attachment->file_path ?? '', PATHINFO_EXTENSION));
                    $isImage = str_starts_with($attachment->mime_type ?? '', 'image/') || in_array($ext, $imageExtensions, true);
                @endphp
                <div class="col-6 col-md-4 col-xl-3">
                    <div class="position-relative border rounded overflow-hidden h-100 {{ $isRemoving ? 'opacity-50' : '' }}"
                         id="existing-attachment-card-{{ $attachment->id }}">
                        @if ($isImage)
                            <a href="{{ route('inventory.products.attachments.download', [$product, $attachment]) }}"
                               data-fancybox="product-attachments-edit"
                               data-caption="{{ $attachment->original_name }}">
                                <img
                                    class="w-100 d-block"
                                    src="{{ route('inventory.products.attachments.download', [$product, $attachment]) }}"
                                    alt="{{ $attachment->original_name }}"
                                    style="height: 150px; object-fit: cover;"
                                />
                            </a>
                        @else
                            <a
                                class="d-flex flex-column align-items-center justify-content-center text-decoration-none h-100 p-3 bg-2"
                                href="{{ route('inventory.products.attachments.download', [$product, $attachment]) }}"
                                target="_blank"
                                style="min-height: 150px;"
                            >
                                <i class="fs-xl fg-secondary mb-2" data-lucide="file-text"></i>
                                <small class="text-truncate d-block w-100 text-center">{{ $attachment->original_name }}</small>
                            </a>
                        @endif
                        <div class="p-2 border-top bg-white">
                            <div class="form-check mb-0">
                                <input class="form-check-input existing-attachment-remove" id="remove-attachment-{{ $attachment->id }}"
                                       type="checkbox" name="removed_attachment_ids[]" value="{{ $attachment->id }}"
                                       {{ $isRemoving ? 'checked' : '' }}
                                       onchange="toggleExistingAttachment('{{ $attachment->id }}')">
                                <label class="form-check-label small" for="remove-attachment-{{ $attachment->id }}">
                                    {{ __('products.attachments.remove') }}
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif

<div class="mb-3">
    <label class="form-label fw-bold">{{ __('products.attachments.new') }}</label>
    <input id="product-image-picker" type="file" class="d-none" accept="{{ UploadType::DOCUMENT_AND_IMAGES->accept() }}">
    <input id="product-images-upload" name="images[]" type="file" class="d-none" multiple>
    <div id="product-image-slot-grid" class="row g-2"></div>
    <div class="form-text mt-2">{{ __('products.attachments.hint') }}</div>
    <div class="invalid-feedback d-none"
         id="product-images-size-error"
         data-too-large-image="{{ __('products.attachments.too_large_image') }}"
         data-too-large-doc="{{ __('products.attachments.too_large_doc') }}"
         data-too-large="{{ __('products.attachments.too_large') }}"></div>
    @error('images')
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
    @error('images.*')
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
    @error('removed_attachment_ids')
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
    @error('removed_attachment_ids.*')
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
</div>

<script>
    const productMaxAttachments = 10;
    const productExistingAttachmentCount = {{ isset($product) ? $product->attachments->count() : 0 }};
    const productRemovedAttachmentIds = new Set(@json(old('removed_attachment_ids', [])));
    const productImageSlots = Array.from({length: productMaxAttachments}, () => null);
    let productPickerTargetIndex = null;

    function isProductImageFile(file) {
        return file.type && file.type.startsWith('image/');
    }

    function productMaxSizeForFile(file) {
        return isProductImageFile(file) ? 8 * 1024 * 1024 : 20 * 1024 * 1024;
    }

    function productTooLargeMessage(file, errorBox) {
        if (isProductImageFile(file)) {
            return errorBox?.dataset.tooLargeImage || errorBox?.dataset.tooLarge || '';
        }
        return errorBox?.dataset.tooLargeDoc || errorBox?.dataset.tooLarge || '';
    }

    function syncProductImagesUpload() {
        const upload = document.getElementById('product-images-upload');
        if (!upload) return;
        const dt = new DataTransfer();
        productImageSlots.forEach((file) => {
            if (file) dt.items.add(file);
        });
        upload.files = dt.files;
    }

    function toggleExistingAttachment(id) {
        const checkbox = document.getElementById(`remove-attachment-${id}`);
        const card = document.getElementById(`existing-attachment-card-${id}`);
        if (!checkbox || !card) return;

        if (checkbox.checked) {
            productRemovedAttachmentIds.add(id);
            card.classList.add('opacity-50');
        } else {
            productRemovedAttachmentIds.delete(id);
            card.classList.remove('opacity-50');
        }

        renderProductImageSlots();
    }

    function getProductAttachmentLimit() {
        return Math.max(0, productMaxAttachments - productExistingAttachmentCount + productRemovedAttachmentIds.size);
    }

    function renderProductImageSlots() {
        const grid = document.getElementById('product-image-slot-grid');
        if (!grid) return;

        const limit = getProductAttachmentLimit();
        grid.innerHTML = '';

        for (let index = 0; index < limit; index++) {
            const file = productImageSlots[index];
            const col = document.createElement('div');
            col.className = 'col-6 col-md-4 col-xl-3';

            const card = document.createElement('div');
            card.className = 'border rounded h-100 bg-2 position-relative overflow-hidden';

            if (file) {
                const removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.className = 'btn btn-sm btn-solid theme-danger position-absolute top-0 end-0 m-1';
                removeBtn.innerHTML = '&times;';
                removeBtn.title = @json(__('products.attachments.remove'));
                removeBtn.setAttribute('aria-label', @json(__('products.attachments.remove')));
                removeBtn.onclick = () => clearProductImageSlot(index);
                card.appendChild(removeBtn);

                if (isProductImageFile(file)) {
                    const preview = document.createElement('img');
                    preview.className = 'w-100 d-block';
                    preview.style.height = '150px';
                    preview.style.objectFit = 'cover';
                    preview.alt = file.name;

                    const reader = new FileReader();
                    reader.onload = (e) => {
                        preview.src = e.target.result;
                    };
                    reader.readAsDataURL(file);
                    card.appendChild(preview);
                } else {
                    const iconWrap = document.createElement('div');
                    iconWrap.className = 'd-flex flex-column align-items-center justify-content-center h-100 p-3';
                    iconWrap.style.minHeight = '150px';
                    iconWrap.innerHTML = `<i class="fs-xl fg-secondary mb-2" data-lucide="file-text"></i><small class="text-truncate d-block w-100 text-center"></small>`;
                    const nameEl = iconWrap.querySelector('small');
                    if (nameEl) {
                        nameEl.textContent = file.name;
                        nameEl.title = file.name;
                    }
                    card.appendChild(iconWrap);
                }

                const footer = document.createElement('div');
                footer.className = 'p-2 border-top bg-white';
                const name = document.createElement('small');
                name.className = 'text-truncate d-block';
                name.textContent = file.name;
                name.title = file.name;
                footer.appendChild(name);
                card.appendChild(footer);
            } else {
                card.className += ' p-3 d-flex flex-column align-items-center justify-content-center text-center';
                card.style.minHeight = '150px';

                const label = document.createElement('label');
                label.className = 'w-100 h-100 mb-0 d-flex flex-column align-items-center justify-content-center';
                label.style.cursor = 'pointer';
                label.innerHTML = `
                    <i class="mb-2" data-lucide="paperclip"></i>
                    <span class="small fw-semibold">{{ __('products.attachments.new') }}</span>
                    <span class="fg-secondary small">{{ __('products.attachments.select_file') }}</span>
                `;
                label.addEventListener('click', (e) => {
                    e.preventDefault();
                    productPickerTargetIndex = index;
                    const picker = document.getElementById('product-image-picker');
                    if (picker) {
                        picker.value = '';
                        picker.click();
                    }
                });
                card.appendChild(label);
            }

            col.appendChild(card);
            grid.appendChild(col);
        }

        for (let index = limit; index < productImageSlots.length; index++) {
            productImageSlots[index] = null;
        }

        syncProductImagesUpload();

        if (window.lucide && typeof window.lucide.createIcons === 'function') {
            window.lucide.createIcons();
        }
    }

    function handleProductImageSelect(file, index) {
        const errorBox = document.getElementById('product-images-size-error');
        if (errorBox) {
            errorBox.classList.add('d-none');
            errorBox.classList.remove('d-block');
            errorBox.textContent = '';
        }

        if (!file) return;

        const limit = productMaxSizeForFile(file);
        if (file.size > limit) {
            if (errorBox) {
                errorBox.textContent = (productTooLargeMessage(file, errorBox) || '') + ' ' + file.name;
                errorBox.classList.remove('d-none');
                errorBox.classList.add('d-block');
            }
            return;
        }

        productImageSlots[index] = file;
        renderProductImageSlots();
    }

    function clearProductImageSlot(index) {
        productImageSlots[index] = null;
        renderProductImageSlots();
    }

    document.addEventListener('DOMContentLoaded', () => {
        const picker = document.getElementById('product-image-picker');
        if (picker) {
            picker.addEventListener('change', (event) => {
                const file = event.target.files && event.target.files[0] ? event.target.files[0] : null;
                if (file && productPickerTargetIndex !== null) {
                    handleProductImageSelect(file, productPickerTargetIndex);
                }
                productPickerTargetIndex = null;
                event.target.value = '';
            });
        }
        renderProductImageSlots();
    });
</script>