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

namespace App\Http\Requests\System\Role;

use App\Enums\DashboardRole;
use App\Enums\FileAction;
use App\Enums\FileResources;
use Illuminate\Validation\Rule;

/**
 * The fields a role is created from.
 *
 * The rules live here rather than in the controller so that the controller can be
 * handed a request that has already been checked. A rule stated once cannot be
 * stated twice in two places that then disagree.
 */
class StoreRoleRequest extends RoleRequest
{
    /**
     * Who may create a role.
     *
     * The permission lives here rather than in the controller, so that the request
     * says everything about who may send it and what it must contain. Laravel
     * asks this before the controller runs, so a request that is not permitted
     * never reaches the action it was meant to guard.
     */
    public function authorize(): bool
    {
        return $this->allows(FileResources::ROLES, FileAction::ADD);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                'unique:roles,name',
            ],
            'dashboard' => [
                'required',
                Rule::enum(DashboardRole::class),
            ],
        ];
    }
}
