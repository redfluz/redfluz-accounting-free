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
 * All code, concepts and intellectual property contained in this file are the
 * exclusive property of redfluz. Unauthorized copying, distribution, modification,
 * reverse engineering, or public display of this software via any medium is
 * strictly prohibited without explicit written permission from the copyright owner.
 */

namespace App\Http\Controllers\Menu;

use App\Constants\FlashMessage;
use App\Enums\FileResources;
use App\Http\Controllers\Controller;
use App\Http\Requests\Menu\PageGroupingPermission\BulkUpdatePageGroupingPermissionRequest;
use App\Http\Requests\Menu\PageGroupingPermission\UpdatePageGroupingPermissionRequest;
use App\Models\Menu\Folder;
use App\Models\Menu\PageGrouping;
use App\Models\Menu\PageGroupingPermission;
use App\Models\System\Role;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class PageGroupingPermissionController extends Controller
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
        $this->allows(FileResources::PAGE_GROUPING_PERMISSIONS);

        /** @var Collection<int, Role> $roles */
        $roles = Role::query()
            ->select('id', 'name')
            ->get();
        /** @var Collection<int, Folder> $folders */
        $folders = Folder::query()
            ->select('id', 'name')
            ->get();
        /** @var Collection<int, PageGrouping> $pageGroupings */
        $pageGroupings = PageGrouping::query()
            ->select('id', 'name', 'folder_id')
            ->get();

        $query = PageGroupingPermission::query()
            ->with(['role:id,name', 'pageGrouping:id,name,folder_id']);

        if ($request->filled('role_id')) {
            $query->where('role_id', $request->role_id);
        }

        if ($request->filled('folder_id')) {
            $query->whereHas('pageGrouping', function ($q) use ($request) {
                $q->where('folder_id', $request->folder_id);
            });
        }

        /** @var Collection<int, PageGroupingPermission> $permissions */
        $permissions = $query->get();

        if ($request->ajax()) {
            return response()->json([
                'html' => view('menu.page-grouping-permissions.partials.table', compact('permissions'))
                    ->render(),
                'count' => $permissions->count(),
            ]);
        }

        return view('menu.page-grouping-permissions.index', compact('roles', 'folders', 'permissions'));
    }

    /**
     * Update an existing resource by its object.
     *
     * @param  UpdatePageGroupingPermissionRequest  $request  The validated form request instance containing updated data.
     * @param  PageGroupingPermission  $pageGroupingPermission  The page grouping permission resource instance.
     * @return JsonResponse JSON response confirming updated.
     */
    public function update(UpdatePageGroupingPermissionRequest $request, PageGroupingPermission $pageGroupingPermission): JsonResponse
    {
        $pageGroupingPermission->update([
            'can_read' => $request->validated('can_read'),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => FlashMessage::updated(FileResources::PAGE_GROUPING_PERMISSIONS),
        ]);
    }

    /**
     * Bulk Update an existing resource by its object.
     *
     * @param  BulkUpdatePageGroupingPermissionRequest  $request  The validated form request instance containing updated data.
     * @return JsonResponse JSON response confirming updated.
     *
     * @throws Throwable
     */
    public function bulkUpdate(BulkUpdatePageGroupingPermissionRequest $request): JsonResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated) {
            /** @var array<int|string, mixed> $changes */
            $changes = $validated['changes'] ?? [];
            foreach ($changes as $id => $canRead) {
                PageGroupingPermission::query()
                    ->where('id', $id)
                    ->update([
                        'can_read' => $canRead,
                    ]);
            }
        });

        return response()->json([
            'status' => 'success',
            'message' => FlashMessage::updated(FileResources::PAGE_GROUPING_PERMISSIONS),
        ]);
    }
}
