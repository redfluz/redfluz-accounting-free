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

use App\Http\Controllers\Menu\FolderPermissionController;
use App\Http\Controllers\Menu\PageGroupingPermissionController;
use App\Http\Controllers\Menu\PagePermissionController;
use Illuminate\Support\Facades\Route;

Route::get('folder-permissions', [FolderPermissionController::class, 'index'])->name('menu.folder-permissions.index');
Route::patch('folder-permissions/{folderPermission}', [FolderPermissionController::class, 'update'])->name('menu.folder-permissions.update');
Route::post('folder-permissions/bulk-update', [FolderPermissionController::class, 'bulkUpdate'])->name('menu.folder-permissions.bulk-update');

Route::get('page-permissions', [PagePermissionController::class, 'index'])->name('menu.page-permissions.index');
Route::patch('page-permissions/{pagePermission}', [PagePermissionController::class, 'update'])->name('menu.page-permissions.update');
Route::post('page-permissions/bulk-update', [PagePermissionController::class, 'bulkUpdate'])->name('menu.page-permissions.bulk-update');

Route::get('page-grouping-permissions', [PageGroupingPermissionController::class, 'index'])->name('menu.page-grouping-permissions.index');
Route::patch('page-grouping-permissions/{pageGroupingPermission}', [PageGroupingPermissionController::class, 'update'])->name('menu.page-grouping-permissions.update');
Route::post('page-grouping-permissions/bulk-update', [PageGroupingPermissionController::class, 'bulkUpdate'])->name('menu.page-grouping-permissions.bulk-update');
