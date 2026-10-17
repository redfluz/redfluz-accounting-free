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
use App\Http\Requests\Menu\FolderPermission\BulkUpdateFolderPermissionRequest;
use App\Http\Requests\Menu\FolderPermission\UpdateFolderPermissionRequest;
use App\Models\Menu\Folder;
use App\Models\Menu\FolderPermission;
use App\Models\System\Role;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class FolderPermissionController extends Controller
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
        $this->allows(FileResources::FOLDER_PERMISSIONS);

        /** @var Collection<int, Role> $roles */
        $roles = Role::query()
            ->select('id', 'name')
            ->get();
        /** @var Collection<int, Folder> $folders */
        $folders = Folder::query()
            ->select('id', 'name')
            ->get();

        $query = FolderPermission::query()
            ->with(['role:id,name', 'folder:id,name,icon']);

        if ($request->filled('role_id')) {
            $query->where('role_id', $request->role_id);
        }

        if ($request->filled('folder_id')) {
            $query->where('folder_id', $request->folder_id);
        }

        /** @var Collection<int, FolderPermission> $permissions */
        $permissions = $query->get();

        // Return JSON if it's an AJAX filtering request
        if ($request->ajax()) {
            return response()->json([
                'html' => view('menu.folder-permissions.partials.table', compact('permissions'))
                    ->render(),
                'count' => $permissions->count(),
            ]);
        }

        return view('menu.folder-permissions.index', compact('roles', 'folders', 'permissions'));
    }

    /**
     * Update an existing resource by its object.
     *
     * @param  UpdateFolderPermissionRequest  $request  The validated form request containing updated data.
     * @param  FolderPermission  $folderPermission  The folder permission resource instance.
     * @return JsonResponse JSON response confirming updated.
     */
    public function update(UpdateFolderPermissionRequest $request, FolderPermission $folderPermission): JsonResponse
    {
        $folderPermission->update([
            'can_read' => $request->validated('can_read'),
        ]);

        return response()->json(
            [
                'status' => 'success',
                'message' => FlashMessage::updated(FileResources::FOLDER_PERMISSIONS),
            ]);
    }

    /**
     * Bulk Update an existing resource by its object.
     *
     * @param  BulkUpdateFolderPermissionRequest  $request  The validated form request containing updated data.
     * @return JsonResponse JSON response confirming updated.
     *
     * @throws Throwable
     */
    public function bulkUpdate(BulkUpdateFolderPermissionRequest $request): JsonResponse
    {
        // Process updates inside a transaction for efficiency and safety
        DB::transaction(function () use ($request) {
            /** @var array<int|string, mixed> $changes */
            $changes = $request->validated('changes') ?? [];
            foreach ($changes as $id => $canRead) {
                FolderPermission::query()
                    ->where('id', $id)
                    ->update([
                        'can_read' => $canRead,
                    ]);
            }
        });

        return response()->json([
            'status' => 'success',
            'message' => FlashMessage::updated(FileResources::FOLDER_PERMISSIONS),
        ]);
    }
}
