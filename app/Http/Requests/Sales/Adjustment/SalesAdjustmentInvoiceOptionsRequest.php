<?php

declare(strict_types=1);

namespace App\Http\Requests\Sales\Adjustment;

use App\Enums\FileAction;
use App\Enums\FileResources;
use App\Http\Requests\BaseFormRequest;
use App\Support\Traits\ChecksFilePermission;
use Illuminate\Validation\Rule;

class SalesAdjustmentInvoiceOptionsRequest extends BaseFormRequest
{
    use ChecksFilePermission;

    public function authorize(): bool
    {
        return $this->allows(FileResources::SALES_ADJUSTMENTS, FileAction::ADD);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'company_id' => ['required', 'uuid', Rule::exists('companies', 'id')->where('is_debtor', true)],
            'search' => ['nullable', 'string', 'max:255'],
        ];
    }
}
