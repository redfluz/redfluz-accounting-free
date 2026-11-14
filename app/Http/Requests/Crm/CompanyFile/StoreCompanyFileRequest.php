<?php

/**
 * ============================================================================
 *
 * @author      redfluz
 *
 * @github      https://github.com/redfluz
 *
 * @youtube     https://www.youtube.com/@redfluz
 *
 * @contact     corex2020@icloud.com
 *
 * @customer
 *
 * @copyright   Copyright (c) 2026 redfluz. All Rights Reserved.
 * ============================================================================
 *
 * PROPRIETARY AND CONFIDENTIAL
 *
 * All code, concepts, and intellectual property contained in this file are the
 * exclusive property of redfluz. Unauthorized copying, distribution, modification,
 * reverse engineering, or public display of this software via any medium is
 * strictly prohibited without explicit written permission from the copyright owner.
 */

declare(strict_types=1);

namespace App\Http\Requests\Crm\CompanyFile;

use App\Enums\FileAction;
use App\Enums\FileResources;
use App\Enums\UploadType;
use Illuminate\Http\UploadedFile;

/**
 * The fields a company file is created from.
 *
 * The rules live here rather than in the controller so that the controller can be
 * handed a request that has already been checked. A rule stated once cannot be
 * stated twice in two places that then disagree.
 *
 * A company file stores one uploaded document or image per record.
 */
class StoreCompanyFileRequest extends CompanyFileRequest
{
    /**
     * Who may create a company file.
     *
     * The permission lives here rather than in the controller, so that the request
     * says everything about who may send it and what it must contain. Laravel
     * asks this before the controller runs, so a request that is not permitted
     * never reaches the action it was meant to guard.
     *
     * It refuses by redirecting with an explanation and writing the attempt to
     * the log, which is what this check has always done and what returning a
     * plain false would lose.
     */
    public function authorize(): bool
    {
        return $this->allows(FileResources::COMPANY_FILES, FileAction::ADD);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'company_id' => ['required', 'string', 'exists:companies,id'],
            'title' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'attachment' => [
                'nullable',
                'file',
                UploadType::DOCUMENT_AND_IMAGES->rule(),
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! $value instanceof UploadedFile) {
                        return;
                    }

                    $extension = strtolower((string) $value->getClientOriginalExtension());
                    $isImage = in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true);
                    $maxKb = $isImage ? 8000 : 20480;

                    if (($value->getSize() / 1024) > $maxKb) {
                        $fail($isImage
                            ? __('company_files.form.attachment_too_large_image', ['name' => $value->getClientOriginalName()])
                            : __('company_files.form.attachment_too_large_doc', ['name' => $value->getClientOriginalName()]));
                    }
                },
            ],
        ];
    }
}
