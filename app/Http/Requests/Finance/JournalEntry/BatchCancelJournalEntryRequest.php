<?php

declare(strict_types=1);

namespace app\Http\Requests\Finance\JournalEntry;

use App\Enums\FileAction;
use App\Enums\FileResources;
use App\Http\Requests\BaseFormRequest;
use App\Support\Traits\ChecksFilePermission;

class BatchCancelJournalEntryRequest extends BaseFormRequest
{
    use ChecksFilePermission;

    public function authorize(): bool
    {
        return $this->allows(FileResources::JOURNAL_ENTRIES, FileAction::CANCEL);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'entry_ids' => 'required|array|min:1',
            'entry_ids.*' => 'required|string|exists:journal_entries,id',
        ];
    }
}
