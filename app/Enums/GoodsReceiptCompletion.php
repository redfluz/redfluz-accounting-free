<?php

/**
 * ============================================================================
 *
 * @author      redfluz
 *
 * @github      https://github.com/redfluz
 *
 * @youtube     https://youtube.com/@redfluz
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

namespace App\Enums;

/**
 * How much of a purchase order a goods received note accounts for.
 *
 * A receipt has no completion state of its own. Completion is a property of the
 * purchase order it references, recomputed by GoodsReceiptController::approve()
 * as the sum of approved receipts against that order. This enum names that order
 * level state so the goods receipt list can filter and badge on it.
 *
 * NONE is deliberately the catch-all rather than only "no linked order". A
 * receipt whose order is still draft or approved has received nothing either, and
 * every receipt must land in exactly one bucket so the filter never hides rows
 * without the user asking it to.
 */
enum GoodsReceiptCompletion: string
{
    case COMPLETE = 'complete';
    case PARTIAL = 'partial';
    case NONE = 'none';

    /**
     * Resolve the bucket from the linked purchase order receiving flags.
     *
     * @param  bool  $isPartialReceived  Whether the order is partially received.
     * @param  bool  $isReceived  Whether the order is fully received.
     * @return self The matching bucket.
     */
    public static function fromReceivingFlags(bool $isPartialReceived, bool $isReceived): self
    {
        if ($isReceived) {
            return self::COMPLETE;
        }

        if ($isPartialReceived) {
            return self::PARTIAL;
        }

        return self::NONE;
    }

    /**
     * The Bootstrap badge class used to render this bucket.
     *
     * @return string The badge CSS class.
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::COMPLETE => 'theme-success',
            self::PARTIAL => 'theme-warning',
            self::NONE => 'theme-secondary',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::COMPLETE => __('goods_receipts.completion.complete'),
            self::PARTIAL => __('goods_receipts.completion.partial'),
            self::NONE => __('goods_receipts.completion.none'),
        };
    }
}
