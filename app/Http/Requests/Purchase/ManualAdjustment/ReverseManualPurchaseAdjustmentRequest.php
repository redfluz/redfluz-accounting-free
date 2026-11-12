<?php

declare(strict_types=1);

namespace app\Http\Requests\Purchase\ManualAdjustment;

use App\Enums\FileAction;
use App\Enums\FileResources;
use App\Http\Requests\BaseFormRequest;
use App\Support\Traits\ChecksFilePermission;

class ReverseManualPurchaseAdjustmentRequest extends BaseFormRequest
{
    use ChecksFilePermission;

    public function authorize(): bool
    {
        return $this->allows(FileResources::MANUAL_PURCHASE_ADJUSTMENTS, FileAction::APPROVE);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:1000'],
        ];
    }
}
