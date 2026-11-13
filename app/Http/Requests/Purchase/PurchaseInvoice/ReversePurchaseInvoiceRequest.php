<?php

declare(strict_types=1);

namespace App\Http\Requests\Purchase\PurchaseInvoice;

use App\Enums\FileAction;
use App\Enums\FileResources;
use App\Http\Requests\BaseFormRequest;
use App\Support\Traits\ChecksFilePermission;

class ReversePurchaseInvoiceRequest extends BaseFormRequest
{
    use ChecksFilePermission;

    public function authorize(): bool
    {
        return $this->allows(FileResources::PURCHASE_INVOICES, FileAction::APPROVE);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:1000'],
        ];
    }
}
