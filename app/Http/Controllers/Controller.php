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

namespace App\Http\Controllers;

use App\Support\Traits\AttributesDocuments;
use App\Support\Traits\ChecksFilePermission;
use App\Support\Traits\StoresUploads;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AttributesDocuments;
    use AuthorizesRequests, ValidatesRequests;
    use ChecksFilePermission;
    use StoresUploads;

    /**
     * Number of news items to display per page (e.g., 3x3 grid).
     */
    protected const int PER_PAGE_GRID = 9;

    /**
     * Number of news items to display per page (e.g., 3x3 grid).
     */
    protected const int PER_PAGE_GRID_NORMAL = 10;

    protected const int PER_PAGE_GRID_STANDARD = 500;
}
