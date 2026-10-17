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

namespace App\Http\Requests\System\User;

use App\Http\Requests\BaseFormRequest;
use App\Support\Traits\ChecksFilePermission;

/**
 * The fields of a user, named.
 *
 * Creating and editing a user ask for different fields — a new user takes only a
 * name and email, while an edit also accepts a role and password — so each states
 * its own rules. What they share is the naming of the fields, which is here.
 */
abstract class UserRequest extends BaseFormRequest
{
    use ChecksFilePermission;

    /**
     * The name of the user.
     */
    public function name(): string
    {
        return $this->stringOrEmpty('name');
    }

    /**
     * The email of the user.
     */
    public function email(): string
    {
        return $this->stringOrEmpty('email');
    }
}
