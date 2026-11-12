<?php

declare(strict_types=1);

namespace app\Http\Requests\Sales\Quotation;

use App\Enums\FileAction;
use App\Enums\FileResources;

class StoreQuotationCompanyRequest extends QuotationRequest
{
    public function authorize(): bool
    {
        return $this->allows(FileResources::QUOTATIONS, FileAction::ADD);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
        ];
    }
}
