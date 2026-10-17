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

namespace App\Http\Controllers\Personnel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Personnel\Profile\UpdateProfileRequest;
use App\Services\Personnel\ProfileService;
use App\Support\Data\Personnel\ProfileData;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @param  ProfileService  $profileService  The profile service instance.
     */
    public function __construct(private readonly ProfileService $profileService) {}

    /**
     * Show the form for editing an open resource.
     * Display the user's profile form.
     *
     * @param  Request  $request  The HTTP request instance containing updated data.
     * @return View Returns the rendered Blade view.
     */
    public function edit(Request $request): View
    {
        return view('personnel.profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update an existing resource by its object.
     * Update the user's profile information.
     *
     * @param  UpdateProfileRequest  $request  The validated request instance.
     * @return RedirectResponse Redirects to the specified route.
     */
    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $user = $request->user();
        if (! $user) {
            abort(404, 'User record not found.');
        }

        $mustReauthenticate = (bool) $user->must_change_password;

        $data = ProfileData::from($request->validated());

        $this->profileService->updateProfile(
            $user,
            $data->name ?? '',
            $data->locale ?? '',
            $request->file('picture'),
            $data->password,
        );

        if ($mustReauthenticate) {
            $this->profileService->logoutAfterPasswordChange($request);

            return redirect()
                ->route('login')
                ->with('status', 'password-changed');
        }

        return redirect()
            ->route('personnel.profile.edit')
            ->with('status', 'profile-updated');
    }
}
