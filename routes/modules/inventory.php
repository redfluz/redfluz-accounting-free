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

use App\Http\Controllers\Inventory\BrandController;
use App\Http\Controllers\Inventory\ProductCategoryController;
use App\Http\Controllers\Inventory\ProductController;
use App\Http\Controllers\Inventory\ProductTypeController;

Route::get('products', [ProductController::class, 'index'])->name('inventory.products.index');
Route::get('products/create', [ProductController::class, 'create'])->name('inventory.products.create');
Route::post('products', [ProductController::class, 'store'])->name('inventory.products.store');
Route::get('products/{product}', [ProductController::class, 'show'])->name('inventory.products.show');
Route::get('products/{product}/edit', [ProductController::class, 'edit'])->name('inventory.products.edit');
Route::get('products/{product}/attachments/{attachment}', [ProductController::class, 'downloadAttachment'])->name('inventory.products.attachments.download');
Route::put('products/{product}', [ProductController::class, 'update'])->name('inventory.products.update');
Route::delete('products/{product}', [ProductController::class, 'destroy'])->name('inventory.products.destroy');

Route::get('product-categories', [ProductCategoryController::class, 'index'])->name('inventory.product-categories.index');
Route::get('product-categories/create', [ProductCategoryController::class, 'create'])->name('inventory.product-categories.create');
Route::post('product-categories', [ProductCategoryController::class, 'store'])->name('inventory.product-categories.store');
Route::get('product-categories/{productCategory}/edit', [ProductCategoryController::class, 'edit'])->name('inventory.product-categories.edit');
Route::put('product-categories/{productCategory}', [ProductCategoryController::class, 'update'])->name('inventory.product-categories.update');
Route::delete('product-categories/{productCategory}', [ProductCategoryController::class, 'destroy'])->name('inventory.product-categories.destroy');

Route::get('product-types', [ProductTypeController::class, 'index'])->name('inventory.product-types.index');
Route::get('product-types/create', [ProductTypeController::class, 'create'])->name('inventory.product-types.create');
Route::post('product-types', [ProductTypeController::class, 'store'])->name('inventory.product-types.store');
Route::get('product-types/{productType}/edit', [ProductTypeController::class, 'edit'])->name('inventory.product-types.edit');
Route::put('product-types/{productType}', [ProductTypeController::class, 'update'])->name('inventory.product-types.update');
Route::delete('product-types/{productType}', [ProductTypeController::class, 'destroy'])->name('inventory.product-types.destroy');

Route::get('brands', [BrandController::class, 'index'])->name('inventory.brands.index');
Route::get('brands/create', [BrandController::class, 'create'])->name('inventory.brands.create');
Route::post('brands', [BrandController::class, 'store'])->name('inventory.brands.store');
Route::get('brands/{brand}', [BrandController::class, 'show'])->name('inventory.brands.show');
Route::get('brands/{brand}/edit', [BrandController::class, 'edit'])->name('inventory.brands.edit');
Route::put('brands/{brand}', [BrandController::class, 'update'])->name('inventory.brands.update');
Route::delete('brands/{brand}', [BrandController::class, 'destroy'])->name('inventory.brands.destroy');
