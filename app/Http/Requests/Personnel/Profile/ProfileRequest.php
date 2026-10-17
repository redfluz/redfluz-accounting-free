<?php

/**
 * ============================================================================
 *
 * @author      redfluz
 *
 * @github      https://github.com/redfluz
 *
 * @youtube     https://github.com/redfluz
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

namespace App\Http\Requests\Personnel\Profile;

use App\Http\Requests\BaseFormRequest;

/**
 * The fields of a profile.
 *
 * Updating a profile asks for the same fields as other profile actions, but the
 * rules differ. What they share is the naming of the fields, which is here, so a
 * controller asking for a field gets the same name whichever request it was
 * handed.
 */
abstract class ProfileRequest extends BaseFormRequest {}
