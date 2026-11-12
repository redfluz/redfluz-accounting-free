<?php

declare(strict_types=1);

namespace app\Http\Requests\Purchase\ManualAdjustment;

use App\Enums\FileAction;
use App\Enums\FileResources;
use App\Http\Requests\BaseFormRequest;
use App\Support\Traits\ChecksFilePermission;
use Illuminate\Validation\Rule;

class ManualPurchaseAdjustmentInvoiceOptionsRequest extends BaseFormRequest
{
    use ChecksFilePermission;

    public function authorize(): bool
    {
        return $this->hasAccess(FileResources::MANUAL_PURCHASE_ADJUSTMENTS, FileAction::ADD)
            || $this->hasAccess(FileResources::MANUAL_PURCHASE_ADJUSTMENTS, FileAction::UPDATE);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'company_id' => ['required', 'uuid', Rule::exists('companies', 'id')->where('is_creditor', true)],
            'search' => ['nullable', 'string', 'max:255'],
        ];
    }
}
