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

namespace App\Http\Requests\Purchase\GoodsReceipt;

use App\Http\Requests\BaseFormRequest;
use App\Support\Traits\ChecksFilePermission;

/**
 * The fields of a goods receipt, named.
 *
 * Creating and editing a goods receipt ask for the same fields but not quite the
 * same ones, so each states its own rules rather than one inheriting from the
 * other. What they do share is the naming of the fields, which is here, so a
 * controller asking for a named field gets the same thing whichever request it was
 * handed.
 *
 * Each accessor returns null when the field was not supplied, rather than the
 * empty string or a zero. The caller needs to tell "not chosen" from "chosen as
 * nothing", because a receipt with no notes is different from one whose notes were
 * left blank by mistake.
 */
abstract class GoodsReceiptRequest extends BaseFormRequest
{
    use ChecksFilePermission;

    /**
     * The purchase order number being received against.
     *
     * Always present: a receipt without a purchase order has no meaning.
     */
    public function purchaseOrderNumber(): string
    {
        return $this->stringOrEmpty('purchase_order_number');
    }

    /**
     * The date of the receipt.
     *
     * Always present: a receipt without a date has no meaning.
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
