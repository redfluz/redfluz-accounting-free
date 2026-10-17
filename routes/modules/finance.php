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

use App\Http\Controllers\Finance\CurrencyController;
use App\Http\Controllers\Finance\DiscountController;
use App\Http\Controllers\Finance\JournalEntry\CancelledController as JournalEntryCancelledController;
use App\Http\Controllers\Finance\JournalEntry\HistoryController as JournalEntryHistoryController;
use App\Http\Controllers\Finance\JournalEntryController;
use App\Http\Controllers\Finance\LedgerController;
use App\Http\Controllers\Finance\TaxController;
use Illuminate\Support\Facades\Route;

Route::get('ledgers/export-pdf', [LedgerController::class, 'exportPdf'])->name('finance.ledgers.export-pdf');
Route::get('ledgers', [LedgerController::class, 'index'])->name('finance.ledgers.index');
Route::get('ledgers/export-excel', [LedgerController::class, 'exportExcel'])
    ->name('finance.ledgers.export-excel');

Route::get('taxes', [TaxController::class, 'index'])->name('finance.taxes.index');
Route::get('taxes/create', [TaxController::class, 'create'])->name('finance.taxes.create');
Route::post('taxes', [TaxController::class, 'store'])->name('finance.taxes.store');
Route::get('taxes/{tax}/edit', [TaxController::class, 'edit'])->name('finance.taxes.edit');
Route::put('taxes/{tax}', [TaxController::class, 'update'])->name('finance.taxes.update');
Route::delete('taxes/{tax}', [TaxController::class, 'destroy'])->name('finance.taxes.destroy');

Route::get('discounts', [DiscountController::class, 'index'])->name('finance.discounts.index');
Route::get('discounts/create', [DiscountController::class, 'create'])->name('finance.discounts.create');
Route::post('discounts', [DiscountController::class, 'store'])->name('finance.discounts.store');
Route::get('discounts/{discount}/edit', [DiscountController::class, 'edit'])->name('finance.discounts.edit');
Route::put('discounts/{discount}', [DiscountController::class, 'update'])->name('finance.discounts.update');
Route::delete('discounts/{discount}', [DiscountController::class, 'destroy'])->name('finance.discounts.destroy');

Route::get('journal-entries', [JournalEntryController::class, 'index'])->name('finance.journal-entries.index');
Route::get('journal-entries/cancelled', [JournalEntryCancelledController::class, 'index'])->name('finance.journal-entries.cancelled.index');
Route::get('journal-entries/cancelled/export-excel', [JournalEntryCancelledController::class, 'exportExcel'])->name('finance.journal-entries.cancelled.export-excel');
Route::get('journal-entries/cancelled/export-pdf', [JournalEntryCancelledController::class, 'exportPdf'])->name('finance.journal-entries.cancelled.export-pdf');
Route::get('journal-entries/history', [JournalEntryHistoryController::class, 'index'])->name('finance.journal-entries.history.index');
Route::get('journal-entries/history/export-excel', [JournalEntryHistoryController::class, 'exportExcel'])->name('finance.journal-entries.history.export-excel');
Route::get('journal-entries/history/export-pdf', [JournalEntryHistoryController::class, 'exportPdf'])->name('finance.journal-entries.history.export-pdf');
Route::get('journal-entries/history/{journalEntry}', [JournalEntryHistoryController::class, 'show'])->name('finance.journal-entries.history.show');
Route::patch('journal-entries/cancelled/{journalEntry}/uncancel', [JournalEntryCancelledController::class, 'uncancel'])->name('finance.journal-entries.cancelled.uncancel');
Route::get('journal-entries/cancelled/{journalEntry}', [JournalEntryCancelledController::class, 'show'])->name('finance.journal-entries.cancelled.show');
Route::get('journal-entries/create', [JournalEntryController::class, 'create'])->name('finance.journal-entries.create');
Route::post('journal-entries', [JournalEntryController::class, 'store'])->name('finance.journal-entries.store');
Route::get('journal-entries/{journalEntry}/pdf', [JournalEntryController::class, 'pdf'])->name('finance.journal-entries.pdf');
Route::get('journal-entries/{journalEntry}/edit', [JournalEntryController::class, 'edit'])->name('finance.journal-entries.edit');
Route::put('journal-entries/{journalEntry}', [JournalEntryController::class, 'update'])->name('finance.journal-entries.update');
Route::patch('journal-entries/{journalEntry}/approve', [JournalEntryController::class, 'approve'])->name('finance.journal-entries.approve');
Route::post('journal-entries/batch-approve', [JournalEntryController::class, 'batchApprove'])->name('finance.journal-entries.batch-approve');
Route::post('journal-entries/batch-cancel', [JournalEntryController::class, 'batchCancel'])->name('finance.journal-entries.batch-cancel');
Route::patch('journal-entries/{journalEntry}/reverse', [JournalEntryController::class, 'reverse'])->name('finance.journal-entries.reverse');
Route::delete('journal-entries/{journalEntry}', [JournalEntryController::class, 'destroy'])->name('finance.journal-entries.destroy');
Route::get('journal-entries/{journalEntry}', [JournalEntryController::class, 'show'])->name('finance.journal-entries.show');

Route::get('currencies', [CurrencyController::class, 'index'])->name('finance.currencies.index');
Route::get('currencies/create', [CurrencyController::class, 'create'])->name('finance.currencies.create');
Route::post('currencies', [CurrencyController::class, 'store'])->name('finance.currencies.store');
Route::get('currencies/{currency}/edit', [CurrencyController::class, 'edit'])->name('finance.currencies.edit');
Route::put('currencies/{currency}', [CurrencyController::class, 'update'])->name('finance.currencies.update');
Route::delete('currencies/{currency}', [CurrencyController::class, 'destroy'])->name('finance.currencies.destroy');
