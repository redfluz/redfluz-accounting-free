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

namespace App\Http\Requests\Finance\JournalEntry;

use App\Enums\FileAction;
use App\Enums\FileResources;

/**
 * The fields a journal entry is edited from.
 *
 * The rules are the same as the create request, because a journal entry is a
 * manual adjustment and the fields do not change between creation and editing.
 * They are stated in full rather than inherited so that a reader can see what an
 * edit accepts without looking elsewhere.
 */
class UpdateJournalEntryRequest extends JournalEntryRequest
{
    /**
     * Who may edit a journal entry.
     *
     * The permission lives here rather than in the controller, so that the request
     * says everything about who may send it and what it must contain. Laravel
     * asks this before the controller runs, so a request that is not permitted
     * never reaches the action it was meant to guard.
     *
     * @return bool Whether the authenticated user may perform this action.
     */
    public function authorize(): bool
    {
        return $this->allows(FileResources::JOURNAL_ENTRIES, FileAction::UPDATE);
    }

    /**
     * The validation rules for editing a journal entry.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'date' => ['required', 'date'],
            'reference_number' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'size:10'],
            'items.*.chart_id' => ['nullable', 'uuid'],
            'items.*.description' => ['nullable', 'string', 'max:255'],
            'items.*.debit_amount' => ['nullable', 'numeric', 'min:0'],
            'items.*.credit_amount' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
