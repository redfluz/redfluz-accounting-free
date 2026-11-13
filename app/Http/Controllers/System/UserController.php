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

namespace App\Http\Controllers\System;

use App\Constants\FlashMessage;
use App\Enums\FileAction;
use App\Enums\FileResources;
use App\Http\Controllers\Controller;
use App\Http\Requests\System\User\StoreUserRequest;
use App\Http\Requests\System\User\UpdateUserRequest;
use App\Models\System\Role;
use App\Models\System\User;
use App\Support\Data\System\UserData;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  Request  $request  The HTTP request instance.
     * @return View Returns the rendered Blade view.
     */
    public function index(Request $request): View
    {
        $this->allows(FileResources::USERS);

        $search = $request->input('search');

        $query = User::query()->with(['role']);

        if (is_string($search) && $search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                    ->orWhere('email', 'like', "%$search%");
            });
        }

        $users = $query
            ->latest()
            ->paginate(self::PER_PAGE_GRID_NORMAL)
            ->withQueryString();

        return view('system.users.index', compact('users', 'search'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return View Returns the rendered Blade view.
     */
    public function create(): View
    {
        $this->allows(FileResources::USERS, FileAction::ADD);
        $rawPassword = config('auth.default_user_password');
        $defaultPassword = is_string($rawPassword) ? $rawPassword : 'password';

        return view('system.users.create', compact('defaultPassword'));
    }

    /**
     * Handle the incoming request to create a new resource.
     * Validates the incoming request data and stores a new record in the database.
     *
     * @param  StoreUserRequest  $request  The validated user being created.
     * @return RedirectResponse Redirects to the specified route.
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        $data = UserData::from($request->validated());

        $rawPassword = config('auth.default_user_password');
        $defaultPassword = is_string($rawPassword) ? $rawPassword : 'password';
        abort_if($defaultPassword === '', 500, 'The default user password has not been configured.');

        /** @var string|null $roleId */
        $roleId = Role::query()
            ->select('roles.id')
            ->where('is_default', true)
            ->value('id')
            ?? Role::query()
                ->where('name', 'User')
                ->value('id');

        abort_if($roleId === null, 500, 'A default user role has not been configured.');

        User::query()->create([
            'role_id' => $roleId,
            'name' => $data->name,
            'email' => $data->email,
            'password' => Hash::make($defaultPassword),
            'must_change_password' => true,
        ]);

        return redirect()
            ->route('system.users.index')
            ->with('success', FlashMessage::created(FileResources::USERS));
    }

    /**
     * Show the form for editing an open resource.
     *
     * @param  User  $user  The user resource instance.
     * @return View Returns the rendered Blade view.
     */
    public function edit(User $user): View
    {
        $this->allows(FileResources::USERS, FileAction::UPDATE);
        $roles = Role::all();

        return view('system.users.edit', compact('user', 'roles'));
    }

    /**
     * Update an existing resource by its object.
     *
     * @param  UpdateUserRequest  $request  The validated user being saved.
     * @param  User  $user  The user resource instance.
     * @return RedirectResponse Redirects to the specified route.
     */
    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = UserData::from($request->validated());

        $user->name = $data->name ?? '';
        $user->email = $data->email ?? '';
        $user->role_id = $data->roleId;

        if (! empty($data->password)) {
            $user->password = Hash::make($data->password);
        }

        $user->save();

        return redirect()
            ->route('system.users.index')
            ->with('success', FlashMessage::updated(FileResources::USERS));
    }

    /**
     * Remove a resource by its object.
     *
     * @param  User  $user  The user resource instance.
     * @return RedirectResponse Redirects to the specified route.
     */
    public function destroy(User $user): RedirectResponse
    {
        $this->allows(FileResources::USERS, FileAction::REMOVE);

        $user->delete();

        return redirect()
            ->route('system.users.index')
            ->with('success', FlashMessage::deleted(FileResources::USERS));
    }
}
