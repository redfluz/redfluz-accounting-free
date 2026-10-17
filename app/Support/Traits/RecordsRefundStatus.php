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

namespace App\Support\Traits;

use App\Enums\LedgerStatus;

/**
 * The questions every refund document is asked about its own status.
 *
 * A refund's status decides three separate things: whether its money has already
 * moved, whether it may still be edited, and how the grid should treat it. Those
 * answers are read from the status column in views, in controllers and in the
 * approval path, so they are defined once here rather than restated per model.
 *
 * This is a trait rather than a base class because the two refund kinds are
 * deliberately unrelated models: they sit on different tables and nothing in the
 * application should be able to treat one as the other. A shared parent class would
 * invite exactly that, since a method typed to the parent accepts both. A trait
 * shares the implementation without sharing the type.
 */
trait RecordsRefundStatus
{
    /**
     * Whether this refund has reached the general ledger.
     */
    public function isPosted(): bool
    {
        return $this->status === LedgerStatus::POSTED;
    }

    /**
     * Whether this refund has been cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === LedgerStatus::CANCELLED;
    }

    /**
     * Whether this refund can still be changed.
     *
     * A posted refund has already moved money, so editing it would leave the
     * ledgers describing something the document no longer says.
     */
    public function isEditable(): bool
    {
        return ! $this->isPosted() && ! $this->isCancelled();
    }
}
