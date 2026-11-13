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
use App\Enums\DashboardRole;
use App\Enums\FileAction;
use App\Enums\FileResources;
use App\Http\Controllers\Controller;
use App\Http\Requests\System\Role\StoreRoleRequest;
use App\Http\Requests\System\Role\UpdateRoleRequest;
use App\Models\Menu\Folder;
use App\Models\Menu\Page;
use App\Models\Menu\PageGrouping;
use App\Models\System\Role;
use App\Models\System\User;
use App\Support\Data\System\RoleData;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

final class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  Request  $request  The HTTP request instance.
     * @return View Returns the rendered Blade view.
     */
    public function index(Request $request): View
    {
        $this->allows(FileResources::ROLES);
        $search = $request->input('search');

        $query = Role::query();

        if (is_string($search) && $search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%$search%");
            });
        }

        $roles = $query
            ->withCount('users')
            ->latest()
            ->paginate(self::PER_PAGE_GRID_NORMAL);

        return view('system.roles.index', compact('roles', 'search'));
    }

    /**
     * Display a role and all users assigned to it.
     *
     * @param  Role  $role  The role resource instance.
     * @return View Returns the rendered Blade view.
     */
    public function show(Role $role): View
    {
        $this->allows(FileResources::ROLES);
        $role->load([
            // Untyped: load() receives the relation, not a Builder (Laravel 13).
            'users' => function ($query) {
                /** @var HasMany<User, Role> $query */
                return $query->orderBy('name');
            },
        ]);

        return view('system.roles.show', compact('role'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return View Returns the rendered Blade view.
     */
    public function create(): View
    {
        $this->allows(FileResources::ROLES, FileAction::ADD);
        $dashboards = DashboardRole::cases();

        return view('system.roles.create', compact('dashboards'));
    }

    /**
     * Handle the incoming request to create a new resource.
     *
     * @param  StoreRoleRequest  $request  The validated role being created.
     * @return RedirectResponse Redirects to the specified route.
     *
     * @throws Throwable
     */
    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $data = RoleData::from($request->validated());

        DB::transaction(function () use ($data) {
            $role = Role::query()->create($data->toCreateRow());

            $now = now();

            $folderData = Folder::query()->pluck('id')->map(fn ($id) => [
                'id' => (string) Str::uuid7(),
                'role_id' => $role->id,
                'folder_id' => $id,
                'can_read' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ])->toArray();

            if ($folderData) {
                DB::table('folder_permissions')->insert($folderData);
            }

            $pageData = Page::query()->pluck('id')->map(fn ($id) => [
                'id' => (string) Str::uuid7(),
                'role_id' => $role->id,
                'page_id' => $id,
                'can_read' => 0,
                'can_add' => 0,
                'can_update' => 0,
                'can_remove' => 0,
                'can_approve' => 0,
                'can_cancel' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ])->toArray();

            if ($pageData) {
                DB::table('page_permissions')->insert($pageData);
            }

            // MenuServices reads page_grouping_permissions to decide which
            // groupings a role may see. A role created here without these rows
            // could never show a grouped section, because nothing outside the
            // menu seeder writes this table.
            $pageGroupingData = PageGrouping::query()->pluck('id')->map(fn ($id) => [
                'id' => (string) Str::uuid7(),
                'role_id' => $role->id,
                'page_grouping_id' => $id,
                'can_read' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ])->toArray();

            if ($pageGroupingData) {
                DB::table('page_grouping_permissions')->insert($pageGroupingData);
            }
        });

        return redirect()
            ->route('system.roles.index')
            ->with('success', FlashMessage::created(FileResources::ROLES));
    }

    /**
     * Show the form for editing an open resource.
     *
     * @param  Role  $role  The role resource instance.
     * @return View Returns the rendered Blade view.
     */
    public function edit(Role $role): View
    {
        $this->allows(FileResources::ROLES, FileAction::UPDATE);
        $dashboards = DashboardRole::cases();

        return view('system.roles.edit', compact('role', 'dashboards'));
    }

    /**
     * Update an existing resource by its object.
     *
     * @param  UpdateRoleRequest  $request  The validated role being saved.
     * @param  Role  $role  The role resource instance.
     * @return RedirectResponse Redirects to the specified route.
     */
    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        $data = RoleData::from($request->validated());

        $role->update([
            'name' => $data->name,
            'dashboard' => $data->dashboard,
        ]);

        return redirect()
            ->route('system.roles.index')
            ->with('success', FlashMessage::updated(FileResources::ROLES));
    }

    /**
     * Remove a resource by its object.
     *
     * @param  Role  $role  The role resource instance.
     * @return RedirectResponse Redirects to the specified route.
     */
    public function destroy(Role $role): RedirectResponse
    {
        $this->allows(FileResources::ROLES, FileAction::REMOVE);

        if ($role->users()->exists()) {
            return redirect()
                ->route('system.roles.index')
                ->with('error', 'Cannot delete role assigned to users.');
        }

        $role->delete();

        return redirect()
            ->route('system.roles.index')
            ->with('success', FlashMessage::deleted(FileResources::ROLES));
    }
}
