<?php

declare(strict_types=1);

namespace app\Http\Requests\Purchase\SupplierOutstanding;

use App\Enums\FileResources;

class IndexSupplierOutstandingRequest extends SupplierOutstandingRequest
{
    public function authorize(): bool
    {
        return $this->allows(FileResources::SUPPLIER_OUTSTANDING);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'as_of_date' => ['nullable', 'date'],
            'status' => ['nullable', 'in:outstanding,overdue,due_soon'],
            'export' => ['nullable', 'in:excel,pdf'],
        ];
    }
}
