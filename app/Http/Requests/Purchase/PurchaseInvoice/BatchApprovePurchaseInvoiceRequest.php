<?php

declare(strict_types=1);

namespace app\Http\Requests\Purchase\PurchaseInvoice;

use App\Enums\FileAction;
use App\Enums\FileResources;
use App\Http\Requests\BaseFormRequest;
use App\Support\Traits\ChecksFilePermission;

class BatchApprovePurchaseInvoiceRequest extends BaseFormRequest
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
            'document_numbers' => 'required|array|min:1',
            'document_numbers.*' => 'required|string',
        ];
    }
}
