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

use App\Enums\UploadType;
use Illuminate\Validation\Rules\Password;

class UpdateProfileRequest extends ProfileRequest
{
    /**
     * Who may update their own profile.
     *
     * Every authenticated user may edit their own profile, so there is nothing to
     * check beyond the login Laravel has already performed.
     *
     * @return bool Whether the authenticated user may perform this action.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * The validation rules for updating a profile.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $mustReauthenticate = (bool) $this->user()?->must_change_password;

        return [
            'name' => ['required', 'string', 'max:255'],
            'locale' => ['required', 'string', 'max:255'],
            'picture' => ['nullable', 'image', UploadType::IMAGES->rule(), 'max:2048'],
            'current_password' => [$mustReauthenticate ? 'required' : 'nullable', 'required_with:password', 'current_password'],
            'password' => [$mustReauthenticate ? 'required' : 'nullable', 'confirmed', 'different:current_password', Password::defaults()],
        ];
    }
}
