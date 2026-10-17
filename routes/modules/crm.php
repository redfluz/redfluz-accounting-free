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

use App\Http\Controllers\Crm\CompanyAddressController;
use App\Http\Controllers\Crm\CompanyController;
use App\Http\Controllers\Crm\CompanyFileController;
use App\Http\Controllers\Crm\ContactController;
use Illuminate\Support\Facades\Route;

Route::get('companies', [CompanyController::class, 'index'])->name('crm.companies.index');
Route::get('companies/create', [CompanyController::class, 'create'])->name('crm.companies.create');
Route::post('companies', [CompanyController::class, 'store'])->name('crm.companies.store');
Route::get('companies/{company}', [CompanyController::class, 'show'])->name('crm.companies.show');
Route::get('companies/{company}/edit', [CompanyController::class, 'edit'])->name('crm.companies.edit');
Route::put('companies/{company}', [CompanyController::class, 'update'])->name('crm.companies.update');
Route::delete('companies/{company}', [CompanyController::class, 'destroy'])->name('crm.companies.destroy');
Route::get('company-addresses', [CompanyAddressController::class, 'index'])->name('crm.company-addresses.index');
Route::get('company-addresses/create', [CompanyAddressController::class, 'create'])->name('crm.company-addresses.create-global');
Route::post('company-addresses', [CompanyAddressController::class, 'store'])->name('crm.company-addresses.store-global');
Route::get('companies/{company}/addresses/create', [CompanyAddressController::class, 'create'])->name('crm.company-addresses.create');
Route::post('companies/{company}/addresses', [CompanyAddressController::class, 'store'])->name('crm.company-addresses.store');
Route::get('company-addresses/{companyAddress}/edit', [CompanyAddressController::class, 'edit'])->name('crm.company-addresses.edit');
Route::put('company-addresses/{companyAddress}', [CompanyAddressController::class, 'update'])->name('crm.company-addresses.update');
Route::delete('company-addresses/{companyAddress}', [CompanyAddressController::class, 'destroy'])->name('crm.company-addresses.destroy');

Route::get('contacts', [ContactController::class, 'index'])->name('crm.contacts.index');
Route::get('contacts/create', [ContactController::class, 'create'])->name('crm.contacts.create');
Route::post('contacts', [ContactController::class, 'store'])->name('crm.contacts.store');
Route::get('contacts/{contact}/edit', [ContactController::class, 'edit'])->name('crm.contacts.edit');
Route::put('contacts/{contact}', [ContactController::class, 'update'])->name('crm.contacts.update');
Route::delete('contacts/{contact}', [ContactController::class, 'destroy'])->name('crm.contacts.destroy');

// Supplier Outstanding is served by purchase.supplier-outstanding.* so the menu
// page, its permission row, and FileResources::SUPPLIER_OUTSTANDING all agree.

Route::get('company-files', [CompanyFileController::class, 'index'])->name('crm.company-files.index');
Route::get('company-files/create', [CompanyFileController::class, 'create'])->name('crm.company-files.create');
Route::post('company-files', [CompanyFileController::class, 'store'])->name('crm.company-files.store');
Route::get('company-files/{companyFile}', [CompanyFileController::class, 'show'])->name('crm.company-files.show');
Route::get('company-files/{companyFile}/attachment', [CompanyFileController::class, 'downloadAttachment'])->name('crm.company-files.attachment');
Route::get('company-files/{companyFile}/edit', [CompanyFileController::class, 'edit'])->name('crm.company-files.edit');
Route::put('company-files/{companyFile}', [CompanyFileController::class, 'update'])->name('crm.company-files.update');
Route::delete('company-files/{companyFile}', [CompanyFileController::class, 'destroy'])->name('crm.company-files.destroy');
