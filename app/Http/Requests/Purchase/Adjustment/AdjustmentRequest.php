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

namespace App\Http\Requests\Purchase\Adjustment;

use App\Http\Requests\BaseFormRequest;
use App\Support\Traits\ChecksFilePermission;

/**
 * The fields of a purchase adjustment, named.
 *
 * Creating and editing a purchase adjustment ask for the same fields but not
 * quite the same ones, so each states its own rules rather than one inheriting
 * from the other. What they do share is the naming of the fields, which is here,
 * so a controller asking for a named field gets the same thing whichever request
 * it was handed.
 *
 * Each accessor returns null when the field was not supplied, rather than the
 * empty string. The caller needs to tell "not chosen" from "chosen as nothing",
 * because an adjustment with no notes is different from one whose notes were left
 * blank by mistake.
 */
abstract class AdjustmentRequest extends BaseFormRequest
{
    use ChecksFilePermission;

    /**
     * The invoice document number this adjustment is linked to.
     *
     * Always present: an adjustment without a reference invoice has no meaning.
     */
    public function referenceNumber(): string
    {
        return $this->stringOrEmpty('reference_number');
    }

    /**
     * The supplier company the adjustment is raised against.
     */
    public function companyId(): string
    {
        return $this->stringOrEmpty('company_id');
    }

    /**
     * The debit chart for the adjustment.
     */
    public function debitId(): string
    {
        return $this->stringOrEmpty('debit_id');
    }

    /**
     * The credit chart for the adjustment.
     */
    public function creditId(): string
    {
        return $this->stringOrEmpty('credit_id');
    }

    /**
     * The adjustment subtype: debit note or credit note.
     */
    public function subtype(): string
    {
        return $this->stringOrEmpty('subtype');
    }

    /**
     * The date of the adjustment.
     *
     * Always present: an adjustment without a date has no meaning.
     */
    public function dateValue(): string
    {
        return $this->stringOrEmpty('date');
    }

    /**
     * The quantity being adjusted.
     */
    public function quantity(): int
    {
        $quantity = $this->validated('quantity');

        return is_numeric($quantity) ? (int) $quantity : 0;
    }

    /**
     * The unit price for the adjustment.
     */
    public function unitPrice(): string
    {
        return $this->stringOrEmpty('unit_price');
    }

    /**
     * Free-text notes, if the user wrote any.
     */
    public function notes(): ?string
    {
        return $this->stringOrNull('notes');
    }
}
