<?php

/**
 * ============================================================================
 *
 * @author      redfluz
 *
 * @github      https://github.com/redfluz
 *
 * @youtube     https://youtube.com/@redfluz
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

namespace App\Http\Requests\Bank\Transfer;

use App\Enums\FileAction;
use App\Enums\FileResources;
use App\Enums\UploadType;
use App\Http\Requests\BaseFormRequest;
use App\Models\Bank\Bank;
use App\Support\Traits\ChecksFilePermission;
use Illuminate\Validation\Rule;

/**
 * The fields a bank transfer in is edited from.
 *
 * Stated separately from the cash transfer's, and from the create rules, rather
 * than shared and overridden: a rule inherited and then removed is a rule nobody
 * can read in one place, and the two screens name different accounts.
 */
class UpdateBankTransferRequest extends BaseFormRequest
{
    use ChecksFilePermission;

    /**
     * Who may edit a bank transfer.
     */
    public function authorize(): bool
    {
        return $this->allows(FileResources::BANK_TRANSFERS, FileAction::UPDATE);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'bank_account_id' => [
                'required',
                'uuid',
                Rule::exists((new Bank)->getTable(), 'id')->where('is_active', true),
            ],
            'document_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            // The deposit slip. Optional, because a bank that credits without
            // paperwork is not unusual; capped like every other document upload
            // so a photo of a whole drawer cannot be dropped on the server.
            'attachment' => ['nullable', 'file', UploadType::DOCUMENT_AND_IMAGES->rule(), 'max:5120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
