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

namespace App\Services;

use Brick\Math\BigDecimal;
use InvalidArgumentException;

final class DecimalService
{
    /**
     * Convert a validated numeric value to plain decimal notation for BCMath.
     *
     * @param  string  $value  The numeric input or database aggregate.
     * @return numeric-string The exact decimal value, without float conversion.
     *
     * @throws InvalidArgumentException When the value is not numeric.
     */
    public static function normalize(string $value): string
    {
        if (! is_numeric($value)) {
            throw new InvalidArgumentException('A numeric decimal value is required.');
        }

        $decimal = (string) BigDecimal::of(trim($value));
        if (! is_numeric($decimal)) {
            throw new InvalidArgumentException('The decimal value could not be normalised.');
        }

        return $decimal;
    }
}
