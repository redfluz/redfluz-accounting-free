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

use App\Http\Controllers\System\RoleController;
use App\Http\Controllers\System\SystemSettingController;
use App\Http\Controllers\System\UserController;
use Illuminate\Support\Facades\Route;

Route::get('roles', [RoleController::class, 'index'])->name('system.roles.index');
Route::get('roles/create', [RoleController::class, 'create'])->name('system.roles.create');
Route::post('roles', [RoleController::class, 'store'])->name('system.roles.store');
Route::get('roles/{role}', [RoleController::class, 'show'])->name('system.roles.show');
Route::get('roles/{role}/edit', [RoleController::class, 'edit'])->name('system.roles.edit');
Route::put('roles/{role}', [RoleController::class, 'update'])->name('system.roles.update');
Route::delete('roles/{role}', [RoleController::class, 'destroy'])->name('system.roles.destroy');

Route::get('users', [UserController::class, 'index'])->name('system.users.index');
Route::get('users/create', [UserController::class, 'create'])->name('system.users.create');
Route::post('users', [UserController::class, 'store'])->name('system.users.store');
Route::get('users/{user}/edit', [UserController::class, 'edit'])->name('system.users.edit');
Route::put('users/{user}', [UserController::class, 'update'])->name('system.users.update');
Route::delete('users/{user}', [UserController::class, 'destroy'])->name('system.users.destroy');

Route::get('settings', [SystemSettingController::class, 'index'])->name('system.settings.index');
Route::put('settings', [SystemSettingController::class, 'update'])->name('system.settings.update');
