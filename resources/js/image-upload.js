/**
 * Downscale images in the browser before they are uploaded.
 * ---------------------------------------------------------------------------
 * A photo taken on a phone is routinely 3–12 MB, while a shared PHP host often
 * caps uploads at 2 MB. The server never gets the chance to validate those
 * files: PHP discards them first, which surfaces as the opaque "failed to
 * upload" error.
 *
 * Resizing on the client fixes that at the source instead of depending on a
 * per-server php.ini. A profile picture is displayed at a couple of hundred
 * pixels and a wallpaper spans at most a wide desktop, so shrinking to a
 * sensible maximum keeps the result sharp while dropping the payload by an
 * order of magnitude.
 *
 * The replacement file is written back into the input's FileList, so the form
 * is submitted exactly as if the user had picked the smaller file. Nothing on
 * the server needs to know this happened.
 */

/** Options applied when an input does not declare its own. */
const DEFAULTS = {
    maxWidth: 1600,
    maxHeight: 1600,
    maxBytes: 1_500_000,
    quality: 0.9,
    minQuality: 0.6,
};

/** Image types that may carry transparency and must not become JPEG. */
const ALPHA_TYPES = ['image/png', 'image/webp', 'image/gif'];

/** Browser format support never changes during a page load; probe it once. */
let webpSupport;

/** Resize jobs still running on a form, shared by every compressed input. */
const PENDING = Symbol('imageCompressionPending');

/** Marks a form whose single submit interceptor has already been installed. */
const SUBMIT_HOOKED = Symbol('imageCompressionSubmitHooked');

const supportsType = (type) => {
    if (type === 'image/webp' && webpSupport !== undefined) {
        return webpSupport;
    }

    const canvas = document.createElement('canvas');
    canvas.width = 1;
    canvas.height = 1;

    const promise = new Promise((resolve) => {
        canvas.toBlob((blob) => resolve(blob?.type === type), type);
    });

    if (type === 'image/webp') {
        webpSupport = promise;
    }

    return promise;
};

/** Resolve once the browser has decoded the file, honouring EXIF rotation. */
const loadImage = (file) =>
    new Promise((resolve, reject) => {
        const url = URL.createObjectURL(file);
        const image = new Image();

        image.onload = () => {
            URL.revokeObjectURL(url);
            resolve(image);
        };
        image.onerror = () => {
            URL.revokeObjectURL(url);
            reject(new Error(`Could not decode ${file.name}`));
        };
        image.src = url;
    });

const encode = (canvas, type, quality) =>
    new Promise((resolve) => {
        canvas.toBlob((blob) => resolve(blob), type, quality);
    });

/**
 * Draw the image at a scale and encode it, reducing quality and then size until
 * the result fits the byte budget.
 *
 * @param {HTMLImageElement} image
 * @param {number} scale
 * @param {string} type
 * @param {{maxBytes: number, quality: number, minQuality: number}} options
 */
const encodeWithinBudget = async (image, scale, type, options) => {
    let quality = options.quality;

    for (let resizes = 0; resizes < 6; resizes++) {
        const canvas = document.createElement('canvas');
        canvas.width = Math.max(1, Math.round(image.naturalWidth * scale));
        canvas.height = Math.max(1, Math.round(image.naturalHeight * scale));

        const context = canvas.getContext('2d');

        if (!context) {
            return null;
        }

        context.imageSmoothingEnabled = true;
        context.imageSmoothingQuality = 'high';
        context.drawImage(image, 0, 0, canvas.width, canvas.height);

        // PNG ignores the quality argument, so only re-encode lossy formats.
        const lossy = type === 'image/webp' || type === 'image/jpeg';
        let blob = await encode(canvas, type, quality);

        while (lossy && blob && blob.size > options.maxBytes && quality > options.minQuality) {
            quality = Math.max(options.minQuality, quality - 0.1);
            blob = await encode(canvas, type, quality);
        }

        if (!blob || blob.size <= options.maxBytes) {
            return blob;
        }

        // Still too large: shrink the canvas and try again.
        scale *= 0.85;
    }

    return null;
};

/**
 * Return a smaller replacement for `file`, or the original when it already fits.
 *
 * @param {File} file
 * @param {object} [options]
 * @returns {Promise<File>}
 */
export async function compressImage(file, options = {}) {
    const settings = { ...DEFAULTS, ...options };

    if (!file.type.startsWith('image/')) {
        return file;
    }

    // Canvas would flatten an animated GIF and cannot represent SVG safely, and
    // neither format is accepted by the server anyway.
    if (file.type === 'image/gif' || file.type === 'image/svg+xml') {
        return file;
    }

    let image;

    try {
        image = await loadImage(file);
    } catch (error) {
        console.error('[upload] could not read image', error);

        return file;
    }

    const scale = Math.min(
        1,
        settings.maxWidth / image.naturalWidth,
        settings.maxHeight / image.naturalHeight
    );

    if (scale === 1 && file.size <= settings.maxBytes) {
        return file;
    }

    const canWebp = await supportsType('image/webp');
    const needsAlpha = ALPHA_TYPES.includes(file.type);
    const type = canWebp ? 'image/webp' : needsAlpha ? 'image/png' : 'image/jpeg';

    const blob = await encodeWithinBudget(image, scale, type, settings);

    if (!blob || blob.size >= file.size) {
        return file;
    }

    const extension = blob.type === 'image/webp' ? 'webp' : blob.type === 'image/png' ? 'png' : 'jpg';
    const base = file.name.replace(/\.[^.]+$/, '') || 'image';

    return new File([blob], `${base}.${extension}`, {
        type: blob.type,
        lastModified: file.lastModified,
    });
}

/**
 * Compress whatever the user picks, then put the result back into the input.
 *
 * @param {HTMLInputElement|null} input
 * @param {object} [options]
 */
export function attachImageCompression(input, options = {}) {
    if (!(input instanceof HTMLInputElement) || input.type !== 'file') {
        return;
    }

    const settings = { ...DEFAULTS, ...options };

    if (input.dataset.compressMaxWidth) {
        settings.maxWidth = Number(input.dataset.compressMaxWidth);
    }
    if (input.dataset.compressMaxHeight) {
        settings.maxHeight = Number(input.dataset.compressMaxHeight);
    }
    if (input.dataset.compressMaxBytes) {
        settings.maxBytes = Number(input.dataset.compressMaxBytes);
    }

    const form = input.form;

    // Every compressed input on the form contributes to the same job set, and a
    // single submit listener waits for all of them. One listener per input could
    // let two of them resume the submit independently and post the form twice.
    const pending = form ? (form[PENDING] ??= new Set()) : new Set();

    // Replacing the FileList fires `change` again so previews refresh. Skip our
    // own listener for that pass; otherwise the new file would be encoded twice.
    let replacing = false;

    input.addEventListener('change', () => {
        if (replacing) {
            return;
        }

        const file = input.files?.[0];

        if (!file) {
            return;
        }

        const job = compressImage(file, settings)
            .then((compressed) => {
                if (compressed === file) {
                    return;
                }

                const transfer = new DataTransfer();
                transfer.items.add(compressed);
                input.files = transfer.files;

                replacing = true;
                input.dispatchEvent(new Event('change', { bubbles: true }));
                replacing = false;
            })
            .catch((error) => {
                console.error('[upload] image compression failed', error);
            });

        pending.add(job);
        job.finally(() => pending.delete(job));
    });

    if (!form || form[SUBMIT_HOOKED]) {
        return;
    }

    form[SUBMIT_HOOKED] = true;

    // A submit that lands while a resize is still running would post the
    // original file. Hold the submit until every job settles, then resume.
    form.addEventListener('submit', (event) => {
        if (form.dataset.imageCompressionResuming === '1' || pending.size === 0) {
            return;
        }

        event.preventDefault();

        const submitter = event.submitter instanceof HTMLElement ? event.submitter : undefined;

        Promise.allSettled([...pending]).then(() => {
            form.dataset.imageCompressionResuming = '1';

            if (typeof form.requestSubmit === 'function') {
                form.requestSubmit(submitter);
            } else {
                form.submit();
            }

            delete form.dataset.imageCompressionResuming;
        });
    });
}

/** Wire every opt-in file input on the page. */
export function initImageCompression(root = document) {
    root.querySelectorAll('input[type="file"][data-compress-image]').forEach((input) => {
        attachImageCompression(input);
    });
}
