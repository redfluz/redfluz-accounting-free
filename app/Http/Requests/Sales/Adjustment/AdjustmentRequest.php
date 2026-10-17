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

declare(strict_types=1);

namespace App\Http\Requests\Sales\Adjustment;

use App\Http\Requests\BaseFormRequest;
use App\Support\Traits\ChecksFilePermission;

/**
 * The fields of a sales adjustment, named.
 *
 * Creating and editing a sales adjustment ask for the same fields, so each states
 * its own rules rather than one inheriting from the other. What they do share is
 * the permission check, which is here, so a controller asking for the company or
 * the debit account gets the same field whichever request it was handed.
 */
abstract class AdjustmentRequest extends BaseFormRequest
{
    use ChecksFilePermission;
}
