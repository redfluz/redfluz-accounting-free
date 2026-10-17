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

use App\Http\Requests\BaseFormRequest;
use App\Support\Traits\ChecksFilePermission;

/**
 * The fields of a purchase return, named.
 *
 * Creating and editing a purchase return ask for the same fields but not quite the
 * same ones, so each states its own rules rather than one inheriting from the
 * other. What they do share is the naming of the fields, which is here, so a
 * controller asking for a named field gets the same thing whichever request it was
 * handed.
 *
 * Each accessor returns null when the field was not supplied, rather than the
 * empty string or a zero. The caller needs to tell "not chosen" from "chosen as
 * nothing", because a return with no notes is different from one whose notes were
 * left blank by mistake.
 */
abstract class PurchaseReturnRequest extends BaseFormRequest
{
    use ChecksFilePermission;

    /**
     * The GRN entry being returned against.
     *
     * Always present: a return without a source entry has no meaning.
     */
    public function sourceEntryId(): string
    {
        return $this->stringOrEmpty('source_entry_id');
    }

    /**
     * The quantity being returned.
     *
     * Always present: a return without a quantity has no meaning.
     */
    public function quantity(): string
    {
        return $this->stringOrEmpty('quantity');
    }

    /**
     * The date of the return.
     *
     * Always present: a return without a date has no meaning.
     */
    public function dateValue(): string
    {
        return $this->stringOrEmpty('date');
    }

    /**
     * Free-text notes, if the user wrote any.
     */
    public function notes(): ?string
    {
        return $this->stringOrNull('notes');
    }
}
