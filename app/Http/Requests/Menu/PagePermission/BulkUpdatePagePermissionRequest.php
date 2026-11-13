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

namespace App\Http\Requests\Menu\PagePermission;

use App\Enums\FileAction;
use App\Enums\FileResources;

/**
 * The fields a bulk page permission update is made from.
 *
 * A bulk update sends a nested map: permission IDs to arrays of column-value
 * pairs. The rules here state that shape: an array of changes, each one an
 * array of booleans. Keeping the rules here rather than in the controller means
 * the controller receives a request it can trust without re-stating what it
 * already knows.
 */
class BulkUpdatePagePermissionRequest extends PagePermissionRequest
{
    /**
     * Who may bulk-update page permissions.
     *
     * The permission lives here rather than in the controller, so that the request
     * says everything about who may send it and what it must contain. Laravel
     * asks this before the controller runs, so a request that is not permitted
     * never reaches the action it was meant to guard.
     *
     * @return bool Whether the authenticated user may perform this action.
     */
    public function authorize(): bool
    {
        return $this->allows(FileResources::PAGE_PERMISSIONS, FileAction::UPDATE);
    }

    /**
     * The validation rules for bulk-updating page permissions.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'changes' => ['required', 'array'],
            'changes.*' => ['required', 'array'],
            'changes.*.*' => ['boolean'],
        ];
    }
}
