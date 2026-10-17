<?php

/**
 * ============================================================================
 *
 * @author      redfluz
 *
 * @github      https://github.com/redfluz
 *
 * @youtube     https://www.youtube.com/@redfluz
 *
 * @contact     corex2020@icloud.com
 *
 * @customer
 *
 * @copyright   Copyright (c) 2026 redfluz. All Rights Reserved.
 * ============================================================================
 *
 * PROPRIETARY AND CONFIDENTIAL
 *
 * All code, concepts, and intellectual property contained in this file are the
 * exclusive property of redfluz. Unauthorized copying, distribution, modification,
 * reverse engineering, or public display of this software via any medium is
 * strictly prohibited without explicit written permission from the copyright owner.
 */

declare(strict_types=1);

namespace App\Http\Requests\Bank\BankJournal;

use App\Enums\FileAction;
use App\Enums\FileResources;
use App\Http\Requests\BaseFormRequest;
use App\Support\Traits\ChecksFilePermission;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * The fields a bank journal is edited from.
 *
 * Creating and editing a journal ask for the same fields, stated separately
 * rather than inherited: a rule inherited and then removed is a rule nobody can
 * read in one place.
 */
class UpdateBankJournalRequest extends BaseFormRequest
{
    use ChecksFilePermission;

    /**
     * Who may edit a bank journal.
     *
     * The permission lives here rather than in the controller, so that the request
     * says everything about who may send it and what it must contain.
     */
    public function authorize(): bool
    {
        return $this->allows(FileResources::BANK_JOURNALS, FileAction::UPDATE);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'bank_account_id' => [
                'required',
                'uuid',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $found = DB::table('banks')
                        ->where('id', $value)
                        ->where('is_active', true)
                        ->whereNull('deleted_at')
                        ->exists();

                    if (!$found) {
                        $found = DB::table('cash_accounts')
                            ->where('id', $value)
                            ->where('is_active', true)
                            ->whereNull('deleted_at')
                            ->exists();
                    }

                    if (!$found) {
                        $found = DB::table('e_wallet_accounts')
                            ->where('id', $value)
                            ->where('is_active', true)
                            ->whereNull('deleted_at')
                            ->exists();
                    }

                    if (!$found) {
                        $fail(__('validation.exists'));
                    }
                },
            ],
            'direction' => ['required', 'string', Rule::in(['in', 'out'])],
            'document_date' => ['required', 'date'],
            'offset_chart_id' => [
                'required',
                'uuid',
                Rule::exists('charts', 'id')->whereNull('deleted_at'),
            ],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'description' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
