<?php

declare(strict_types=1);

namespace App\Support\Traits;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Stores and serves uploaded documents.
 *
 * Two decisions live here.
 *
 * Naming. UploadedFile::store() names a file with guessExtension(), which asks a
 * MIME guesser what the content is, so the stored name could disagree with the
 * name the validation rule approved: uploading "report.pdf" whose bytes were
 * actually a zip was stored as "<random>.zip" and then served with a zip content
 * type, defeating the extension allowlist with the very files it policed. The
 * validated client extension is used instead, behind a random base name so the
 * stored path is still not attacker controlled.
 *
 * Disk. The default disk is the private "documents" disk. Documents are
 * financial records, and the public disk is reachable by anyone who knows or
 * guesses a path, with no login and no permission check, so a document must not
 * live there. Interface images that appear inline in views and in generated
 * PDFs, such as logos, profile pictures and product photographs, pass the public
 * disk explicitly instead.
 */
trait StoresUploads
{
    /**
     * The private disk uploaded documents are written to.
     *
     * @var string
     */
    protected const string DOCUMENT_DISK = 'documents';

    /**
     * The public disk interface images are written to.
     *
     * @var string
     */
    protected const string PUBLIC_DISK = 'public';

    /**
     * Store an already-validated upload and return its relative path.
     *
     * @param  UploadedFile  $file  The validated upload.
     * @param  string  $directory  The directory on the disk to store into.
     * @param  string  $disk  The disk to store on.
     * @return string The stored path relative to the disk root.
     *
     * @throws RuntimeException When the file cannot be written to the disk.
     */
    protected function storeUpload(
        UploadedFile $file,
        string $directory,
        string $disk = self::DOCUMENT_DISK
    ): string {
        $path = $file->storeAs($directory, $this->uploadName($file), $disk);

        if ($path === false) {
            throw new RuntimeException(
                sprintf('Failed to store the uploaded file in "%s" on the "%s" disk.', $directory, $disk)
            );
        }

        return $path;
    }

    /**
     * The disk that currently holds a stored document.
     *
     * Documents moved to the private disk, but files uploaded before that change
     * are still on the public disk and their rows still point at them. Rather
     * than rewrite or delete historical rows, a lookup checks the private disk
     * first and falls back to the public one, so nothing already recorded stops
     * working.
     *
     * @param  string|null  $path  The stored path, relative to a disk root.
     * @return string|null The disk holding it, or null when neither has it.
     */
    protected function documentDisk(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        return array_find([self::DOCUMENT_DISK, self::PUBLIC_DISK], fn ($disk) => Storage::disk($disk)->exists($path));

    }

    /**
     * Whether a stored document is present on either disk.
     *
     * @param  string|null  $path  The stored path.
     */
    protected function documentExists(?string $path): bool
    {
        return $this->documentDisk($path) !== null;
    }

    /**
     * Stream a stored document to the browser.
     *
     * The name offered to the browser falls back to the stored file name when the
     * row does not remember the original one, so a download always has
     * something to call itself.
     *
     * @param  string|null  $path  The stored path.
     * @param  string|null  $name  The name to offer the file under, if known.
     * @return StreamedResponse The download, or a 404 when the file is gone.
     */
    protected function downloadDocument(?string $path, ?string $name = null): StreamedResponse
    {
        $disk = $this->documentDisk($path);

        abort_if($disk === null, 404);

        $path = (string) $path;
        $downloadName = ($name === null || $name === '') ? basename($path) : $name;

        return Storage::disk($disk)->download($path, $downloadName);
    }

    /**
     * Delete a stored document from whichever disk holds it.
     *
     * @param  string|null  $path  The stored path.
     */
    protected function deleteDocument(?string $path): void
    {
        $disk = $this->documentDisk($path);

        if ($disk !== null) {
            Storage::disk($disk)->delete((string) $path);
        }
    }

    /**
     * Delete several stored documents from whichever disks hold them.
     *
     * @param  iterable<int, string|null>  $paths  The stored paths.
     */
    protected function deleteDocuments(iterable $paths): void
    {
        foreach ($paths as $path) {
            $this->deleteDocument(is_string($path) ? $path : null);
        }
    }

    /**
     * Build the random on-disk name for an upload.
     *
     * Prefers the client extension, which the allowlist already approved, and
     * falls back to the guessed extension only when the client sent none or sent
     * something that is not a plain extension, so the stored name can never
     * contain a path separator or an unexpected suffix.
     *
     * @param  UploadedFile  $file  The validated upload.
     * @return string The generated file name.
     */
    private function uploadName(UploadedFile $file): string
    {
        $extension = strtolower($file->getClientOriginalExtension());

        if ($extension === '' || preg_match('/^[a-z0-9]{1,10}$/', $extension) !== 1) {
            $extension = strtolower((string) $file->guessExtension());
        }

        return Str::random(40).($extension === '' ? '' : '.'.$extension);
    }
}
