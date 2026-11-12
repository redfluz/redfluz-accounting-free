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

namespace app\Http\Requests\Finance\JournalEntry;

use App\Enums\FileAction;
use App\Enums\FileResources;
use Illuminate\Validation\Rule;

/**
 * The search a journal entry account picker sends when looking for an account.
 *
 * The picker is opened from both the create form and the edit form, so anyone who
 * may raise either of them may search the chart of accounts for it. Nothing the
 * search returns is written: it only lists accounts to choose from.
 */
class SearchJournalEntryChartsRequest extends JournalEntryRequest
{
    /**
     * Who may search the chart of accounts for a journal entry.
     *
     * @return bool Whether the authenticated user may create or edit a journal entry.
     */
    public function authorize(): bool
    {
        return $this->hasAccess(FileResources::JOURNAL_ENTRIES, FileAction::ADD)
            || $this->hasAccess(FileResources::JOURNAL_ENTRIES, FileAction::UPDATE);
    }

    /**
     * The validation rules for the account search.
     *
     * @return array<string, mixed> The rules, keyed by field name.
     */
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:255'],
            'type_id' => ['nullable', 'uuid', Rule::exists('chart_types', 'id')],
        ];
    }
}
