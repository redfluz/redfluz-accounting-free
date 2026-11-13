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

namespace App\Http\Requests\Purchase\PurchaseReturn;

use App\Enums\FileAction;
use App\Enums\FileResources;

/**
 * The fields a purchase return is created from.
 *
 * The rules live here rather than in the controller so that the controller can be
 * handed a request that has already been checked, and can ask for a named field
 * instead of reaching into an array it built itself. A rule stated once cannot be
 * stated twice in two places that then disagree.
 */
class StorePurchaseReturnRequest extends PurchaseReturnRequest
{
    /**
     * Who may raise a purchase return.
     *
     * The permission lives here rather than in the controller, so that the request
     * says everything about who may send it and what it must contain. Laravel
     * asks this before the controller runs, so a request that is not permitted
     * never reaches the action it was meant to guard.
     *
     * It refuses by redirecting with an explanation and writing the attempt to
     * the log, which is what this check has always done and what returning a
     * plain false would lose.
     */
    public function authorize(): bool
    {
        return $this->allows(FileResources::PURCHASE_RETURNS, FileAction::ADD);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'source_entry_id' => ['required', 'uuid'],
            'quantity' => ['required', 'numeric', 'gt:0', 'decimal:0,4'],
            'date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
