<?php

declare(strict_types=1);

namespace App\Http\Requests\Sales\ManualAdjustment;

use App\Enums\FileAction;
use App\Enums\FileResources;
use App\Http\Requests\BaseFormRequest;
use App\Support\Traits\ChecksFilePermission;
use Illuminate\Validation\Rule;

class ManualSalesAdjustmentInvoiceOptionsRequest extends BaseFormRequest
{
    use ChecksFilePermission;

    public function authorize(): bool
    {
        return $this->hasAccess(FileResources::MANUAL_SALES_ADJUSTMENTS, FileAction::ADD)
            || $this->hasAccess(FileResources::MANUAL_SALES_ADJUSTMENTS, FileAction::UPDATE);
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
