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
 * The fields a bank transfer in is created from.
 *
 * The rules mirror UpdateBankTransferRequest. They are stated in full here so
 * each concrete request can be read independently without tracing the update
 * class. The deposit slip is optional on create: a bank that credits without
 * paperwork is not unusual.
 */
class StoreBankTransferRequest extends BaseFormRequest
{
    use ChecksFilePermission;

    /**
     * Who may create a bank transfer.
     */
    public function authorize(): bool
    {
        return $this->allows(FileResources::BANK_TRANSFERS, FileAction::ADD);
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
            'date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'attachment' => ['nullable', 'file', UploadType::DOCUMENT_AND_IMAGES->rule(), 'max:5120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
