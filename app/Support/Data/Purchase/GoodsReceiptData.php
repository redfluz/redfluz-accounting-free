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

namespace App\Support\Data\Purchase;

use Carbon\CarbonImmutable;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;

/**
 * A goods receipt: goods received against a purchase order.
 *
 * A goods receipt does not have its own model. It writes product ledger entries
 * directly, so this DTO carries the form data: which order it is against, when
 * the goods arrived, and any notes. The lines and attachments are handled
 * separately by the controller.
 *
 * It is built one way:
 *
 *   GoodsReceiptData::from($request->validated())   a form, to raise or edit one
 *
 * The properties are all optional because an edit may change any of them, and a
 * form that left one blank is saying "not this time" rather than "nothing at
 * all".
 */
class GoodsReceiptData extends Data
{
    public function __construct(
        #[MapName('purchase_order_number')]
        public ?string $purchaseOrderNumber = null,

        #[MapName('date')]
        public ?CarbonImmutable $date = null,

        #[MapName('notes')]
        public ?string $notes = null,
    ) {}
}
