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

namespace App\Http\Requests\Finance\Discount;

use App\Http\Requests\BaseFormRequest;
use App\Support\Traits\ChecksFilePermission;

/**
 * The fields of a discount, named.
 *
 * Creating and editing a discount ask for the same fields but not quite the same
 * rules, so each states its own rules rather than one inheriting from the other: a
 * new discount must be unique across the table, while an edit must be unique
 * except for itself. What they do share is the naming of the fields, which is
 * here, so a controller asking for the code gets the same field whichever request
 * it was handed.
 */
abstract class DiscountRequest extends BaseFormRequest
{
    use ChecksFilePermission;
}
