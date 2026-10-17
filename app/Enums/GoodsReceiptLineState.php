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
 * How much of an ordered quantity one goods received note line accounts for.
 *
 * The show page colours a line by this so a reviewer can see at a glance whether
 * the receipt covers what was ordered, without reading two columns against each
 * other. Nothing here is persisted; it is derived per render.
 */
enum GoodsReceiptLineState: string
{
    case COMPLETE = 'complete';
    case PARTIAL = 'partial';
    case NONE = 'none';

    /**
     * Classify one received line against its ordered quantity.
     *
     * @param  numeric-string  $ordered  The quantity on the purchase order line.
     * @param  numeric-string  $received  The quantity booked by this receipt.
     * @return self The matching state.
     */
    public static function classify(string $ordered, string $received): self
    {
        if (bccomp($received, '0', 4) !== 1) {
            return self::NONE;
        }

        // An order line of zero cannot be completed, so treat any receipt against
        // it as partial rather than claiming it is finished.
        if (bccomp($ordered, '0', 4) !== 1) {
            return self::PARTIAL;
        }

        return bccomp($received, $ordered, 4) >= 0
            ? self::COMPLETE
            : self::PARTIAL;
    }

    /**
     * The Bootstrap contextual class used for this row state.
     *
     * @return string The CSS class.
     */
    public function rowClass(): string
    {
        return match ($this) {
            self::COMPLETE => 'table-success',
            self::PARTIAL => 'table-warning',
            self::NONE => 'table-danger',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::COMPLETE => 'theme-success',
            self::PARTIAL => 'theme-warning',
            self::NONE => 'theme-danger',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::COMPLETE => __('goods_receipts.line_state.complete'),
            self::PARTIAL => __('goods_receipts.line_state.partial'),
            self::NONE => __('goods_receipts.line_state.none'),
        };
    }
}
