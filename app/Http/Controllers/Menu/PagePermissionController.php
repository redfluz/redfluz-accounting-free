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

namespace App\Http\Controllers\Menu;

use App\Constants\FlashMessage;
use App\Enums\FileResources;
use App\Http\Controllers\Controller;
use App\Http\Requests\Menu\PagePermission\BulkUpdatePagePermissionRequest;
use App\Http\Requests\Menu\PagePermission\UpdatePagePermissionRequest;
use App\Models\Menu\Folder;
use App\Models\Menu\Page;
use App\Models\Menu\PagePermission;
use App\Models\System\Role;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class PagePermissionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  Request  $request  The HTTP request instance containing updated data.
     * @return View|JsonResponse Returns the rendered Blade view.
     *
     * @throws Throwable
     */
    public function index(Request $request): View|JsonResponse
    {
        $this->allows(FileResources::PAGE_PERMISSIONS);

        /** @var Collection<int, Role> $roles */
        $roles = Role::query()
            ->select('id', 'name')
            ->orderBy('name')
            ->get();
        /** @var Collection<int, Folder> $folders */
        $folders = Folder::query()
            ->select('id', 'name')
            ->orderBy('name')
            ->get();
        /** @var Collection<int, Page> $pages */
        $pages = Page::query()
            ->select('id', 'folder_id', 'name')
            ->orderBy('name')
            ->get();

        $query = PagePermission::query()
            ->with(['role:id,name', 'page:id,folder_id,name', 'page.folder:id,name']);

        $roleId = $request->input('role_id');
        $folderId = $request->input('folder_id');
        $pageId = $request->input('page_id');

        if (is_string($roleId) && $roleId !== '') {
            $query->where('role_id', $roleId);
        }

        if (is_string($folderId) && $folderId !== '') {
            $query->whereHas('page', fn ($pageQuery) => $pageQuery->where('folder_id', $folderId));
        }

        if (is_string($pageId) && $pageId !== '') {
            $query->where('page_id', $pageId);
        }

        /** @var Collection<int, PagePermission> $permissions */
        $permissions = $query->get();

        if ($request->ajax()) {
            return response()->json([
                'html' => view('menu.page-permissions.partials.table', compact('permissions'))
                    ->render(),
                'count' => $permissions->count(),
            ]);
        }

        return view('menu.page-permissions.index', compact('roles', 'folders', 'pages', 'permissions'));
    }

    /**
     * Update an existing resource by its object.
     *
     * @param  UpdatePagePermissionRequest  $request  The form request containing the validated permission column and value.
     * @param  PagePermission  $pagePermission  The page permission resource instance.
     * @return JsonResponse JSON response confirming updated.
     */
    public function update(UpdatePagePermissionRequest $request, PagePermission $pagePermission): JsonResponse
    {
        $permission = $request->validated('permission');
        $pagePermission->update([
            is_string($permission) ? $permission : '' => $request->validated('value'),
        ]);

        return response()->json(
            [
                'status' => 'success',
                'message' => FlashMessage::updated(FileResources::PAGE_PERMISSIONS),
            ]);
    }

    /**
     * Bulk Update an existing resource by its object.
     *
     * @param  BulkUpdatePagePermissionRequest  $request  The form request containing the validated bulk changes.
     * @return JsonResponse JSON response confirming updated.
     *
     * @throws Throwable
     */
    public function bulkUpdate(BulkUpdatePagePermissionRequest $request): JsonResponse
    {
        DB::transaction(function () use ($request) {
            /** @var array<int|string, mixed> $changes */
            $changes = $request->validated()['changes'] ?? [];
            foreach ($changes as $permissionId => $permissions) {
                // Sanitizing keys to avoid updating non-permitted columns
                $allowedColumns = ['can_read', 'can_add', 'can_update', 'can_remove', 'can_approve', 'can_cancel'];
                /** @var array<string, mixed> $permissionsArray */
                $permissionsArray = is_array($permissions) ? $permissions : [];
                /** @var array<string, mixed> $updateData */
                $updateData = array_intersect_key($permissionsArray, array_flip($allowedColumns));

                if (! empty($updateData)) {
                    PagePermission::query()
                        ->where('id', $permissionId)
                        ->update($updateData);
                }
            }
        });

        return response()
            ->json(
                [
                    'status' => 'success',
                    'message' => FlashMessage::updated(FileResources::PAGE_PERMISSIONS),
                ]);
    }
}
