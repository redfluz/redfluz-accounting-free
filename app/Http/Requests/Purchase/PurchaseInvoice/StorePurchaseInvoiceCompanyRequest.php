<?php

declare(strict_types=1);

namespace App\Http\Requests\Purchase\PurchaseInvoice;

use App\Enums\FileAction;
use App\Enums\FileResources;
use App\Http\Requests\BaseFormRequest;
use App\Support\Traits\ChecksFilePermission;

class StorePurchaseInvoiceCompanyRequest extends BaseFormRequest
{
    use ChecksFilePermission;

    public function authorize(): bool
    {
        return $this->allows(FileResources::PURCHASE_INVOICES, FileAction::ADD);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
        ];
    }
}
