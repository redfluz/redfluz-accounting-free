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

use App\Enums\FileAction;
use App\Enums\FileResources;
use App\Models\System\User;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * The fields a user is edited from.
 *
 * An edit accepts a role assignment and an optional password change in addition
 * to the name and email. The rules are stated in full rather than inherited so
 * that a reader can see what an edit accepts without looking elsewhere.
 */
class UpdateUserRequest extends UserRequest
{
    /**
     * Who may edit a user.
     *
     * The permission lives here rather than in the controller, so that the request
     * says everything about who may send it and what it must contain. Laravel
     * asks this before the controller runs, so a request that is not permitted
     * never reaches the action it was meant to guard.
     */
    public function authorize(): bool
    {
        return $this->allows(FileResources::USERS, FileAction::UPDATE);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var User $user */
        $user = $this->route('user');
        $userId = $user->id;

        return [
            'role_id' => ['required', 'string', 'exists:roles,id'],
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->withoutTrashed()->ignore($userId),
            ],
            'password' => ['nullable', 'string', Password::defaults()],
        ];
    }
}
