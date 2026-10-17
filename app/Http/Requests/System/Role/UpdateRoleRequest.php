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
use App\Models\System\Role;
use Illuminate\Validation\Rule;

/**
 * The fields a role is edited from.
 *
 * The rules are stated in full rather than taken from the create request and
 * adjusted, because the unique rule must ignore the current role. An edit
 * does not re-state the name uniqueness against itself.
 */
class UpdateRoleRequest extends RoleRequest
{
    /**
     * Who may edit a role.
     *
     * The permission lives here rather than in the controller, so that the request
     * says everything about who may send it and what it must contain. Laravel
     * asks this before the controller runs, so a request that is not permitted
     * never reaches the action it was meant to guard.
     */
    public function authorize(): bool
    {
        return $this->allows(FileResources::ROLES, FileAction::UPDATE);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Role $role */
        $role = $this->route('role');
        $roleId = $role->id;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('roles', 'name')->ignore($roleId),
            ],
            'dashboard' => [
                'required',
                Rule::enum(DashboardRole::class),
            ],
        ];
    }
}
