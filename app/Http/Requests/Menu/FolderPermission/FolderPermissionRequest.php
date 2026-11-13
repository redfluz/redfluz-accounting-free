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

namespace App\Http\Requests\Menu\FolderPermission;

use App\Http\Requests\BaseFormRequest;
use App\Support\Traits\ChecksFilePermission;

/**
 * The fields of a folder permission, named.
 *
 * Updating a single folder permission and bulk-updating many ask for different
 * shapes of input, so each states its own rules. What they share is the naming
 * of the resource and the permission that guards them, which is here so a
 * controller asking for access gets the same check whichever request it was
 * handed.
 */
abstract class FolderPermissionRequest extends BaseFormRequest
{
    use ChecksFilePermission;
}
