<?php

declare(strict_types=1);

namespace App\Http\Requests\Sales\SalesInvoice;

use App\Enums\FileAction;
use App\Enums\FileResources;
use App\Http\Requests\BaseFormRequest;
use App\Support\Traits\ChecksFilePermission;

class ReceiveReturnRequest extends BaseFormRequest
{
    use ChecksFilePermission;

    public function authorize(): bool
    {
        return $this->allows(FileResources::SALES_INVOICES, FileAction::UPDATE);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'returns' => ['sometimes', 'array'],
            'returns.*' => ['array'],
            'returns.*.line_id' => ['required', 'uuid'],
            'returns.*.quantity' => ['nullable', 'numeric', 'min:0'],
            'return_line_id' => ['required_without:returns', 'uuid'],
            'return_quantity' => ['required_without:returns', 'numeric', 'min:0.0001'],
            'return_notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
