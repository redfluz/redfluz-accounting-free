<?php

declare(strict_types=1);

namespace app\Http\Requests\Bank\Bank;

use App\Enums\FileResources;

/**
 * Guards the bank account detail view.
 */
class ShowBankRequest extends BankRequest
{
    public function authorize(): bool
    {
        return $this->allows(FileResources::BANKS);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
        ];
    }
}
