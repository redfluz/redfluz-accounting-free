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

/**
 * The fields a company file is edited from.
 *
 * The rules are stated in full rather than taken from the create request, because
 * the edit takes a single attachment and a remove flag rather than an array, and
 * a rule inherited and then changed is a rule nobody can read in one place.
 */
class UpdateCompanyFileRequest extends CompanyFileRequest
{
    /**
     * Who may edit a company file.
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
        return $this->allows(FileResources::COMPANY_FILES, FileAction::UPDATE);
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
            'remove_attachment' => ['nullable', 'boolean'],
            'attachment' => ['nullable', 'file', UploadType::DOCUMENT_AND_IMAGES->rule(), 'max:10240'],
        ];
    }
}
