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

namespace App\Http\Requests\Bank\Receipt;

use App\Enums\FileAction;
use App\Enums\FileResources;
use App\Enums\PaymentTransferType;
use App\Enums\UploadType;
use Illuminate\Validation\Rule;

/**
 * The fields a receipt is created from.
 *
 * The rules live here rather than in the controller so that the controller can be
 * handed a request that has already been checked, and can ask for a named field
 * instead of reaching into an array it built itself. A rule stated once cannot be
 * stated twice in two places that then disagree.
 *
 * The attachment is offered on creation and not on edit, because the file is
 * attached to the refund when it is raised; replacing it later is a separate act
 * with its own history, and an edit that quietly took a new file would leave two
 * files and one of them unexplained.
 */
class StoreReceiptRequest extends ReceiptRequest
{
    /**
     * Who may raise a refund.
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
        return $this->allows(FileResources::PAYMENTS, FileAction::ADD);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'company_id' => ['required', 'uuid', 'exists:companies,id'],
            'debit_id' => ['nullable', 'uuid', 'different:credit_id', 'exists:charts,id'],
            'credit_id' => ['nullable', 'uuid', 'different:debit_id', 'exists:charts,id'],
            'reference_number' => ['nullable', 'string', 'max:255'],
            'document_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'attachment' => ['nullable', 'file', UploadType::DOCUMENT_AND_IMAGES->rule(), 'max:5120'],
            'notes' => ['nullable', 'string'],
            'payment_transfer_type' => [
                'nullable',
                'string',
                Rule::in(array_column(PaymentTransferType::cases(), 'value')),
            ],
        ];
    }
}
