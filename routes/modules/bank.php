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
 * PROPRIETARY AND CONFIDENTIAL
 *
 * All code, concepts, and intellectual property contained in this file are the
 * exclusive property of redfluz. Unauthorized copying, distribution, modification,
 * reverse engineering, or public display of this software via any medium is
 * strictly prohibited without explicit written permission from the copyright owner.
 */

use App\Http\Controllers\Bank\BankController;
use App\Http\Controllers\Bank\BankJournalController;
use App\Http\Controllers\Bank\BankLedgerController;
use App\Http\Controllers\Bank\CashAccountController;
use App\Http\Controllers\Bank\CashLedgerController;
use App\Http\Controllers\Bank\CustomerRefundController;
use App\Http\Controllers\Bank\EWalletAccountController;
use App\Http\Controllers\Bank\EWalletLedgerController;
use App\Http\Controllers\Bank\EWalletProviderController;
use App\Http\Controllers\Bank\PaymentVoucher\CancelledController as VoucherCancelledController;
use App\Http\Controllers\Bank\PaymentVoucher\HistoryController as VoucherHistoryController;
use App\Http\Controllers\Bank\PaymentVoucherController;
use App\Http\Controllers\Bank\Receipt\CancelledController as ReceiptCancelledController;
use App\Http\Controllers\Bank\Receipt\HistoryController as ReceiptHistoryController;
use App\Http\Controllers\Bank\ReceiptController;
use App\Http\Controllers\Bank\Refund\CustomerCancelledController;
use App\Http\Controllers\Bank\Refund\CustomerHistoryController;
use App\Http\Controllers\Bank\Refund\SupplierCancelledController;
use App\Http\Controllers\Bank\Refund\SupplierHistoryController;
use App\Http\Controllers\Bank\SupplierRefundController;
use Illuminate\Support\Facades\Route;

Route::resource('banks', BankController::class)->names('bank.banks');
Route::get('cash-accounts', [CashAccountController::class, 'index'])->name('bank.cash-accounts.index');
Route::get('cash-accounts/create', [CashAccountController::class, 'create'])->name('bank.cash-accounts.create');
Route::post('cash-accounts', [CashAccountController::class, 'store'])->name('bank.cash-accounts.store');
Route::get('cash-accounts/{cashAccount}', [CashAccountController::class, 'show'])->name('bank.cash-accounts.show');
Route::get('cash-accounts/{cashAccount}/edit', [CashAccountController::class, 'edit'])->name('bank.cash-accounts.edit');
Route::put('cash-accounts/{cashAccount}', [CashAccountController::class, 'update'])->name('bank.cash-accounts.update');
Route::delete('cash-accounts/{cashAccount}', [CashAccountController::class, 'destroy'])->name('bank.cash-accounts.destroy');
Route::get('e-wallet-accounts', [EWalletAccountController::class, 'index'])->name('bank.e-wallet-accounts.index');
Route::get('e-wallet-accounts/create', [EWalletAccountController::class, 'create'])->name('bank.e-wallet-accounts.create');
Route::post('e-wallet-accounts', [EWalletAccountController::class, 'store'])->name('bank.e-wallet-accounts.store');
Route::get('e-wallet-accounts/{eWalletAccount}', [EWalletAccountController::class, 'show'])->name('bank.e-wallet-accounts.show');
Route::get('e-wallet-accounts/{eWalletAccount}/edit', [EWalletAccountController::class, 'edit'])->name('bank.e-wallet-accounts.edit');
Route::put('e-wallet-accounts/{eWalletAccount}', [EWalletAccountController::class, 'update'])->name('bank.e-wallet-accounts.update');
Route::delete('e-wallet-accounts/{eWalletAccount}', [EWalletAccountController::class, 'destroy'])->name('bank.e-wallet-accounts.destroy');

Route::get('e-wallet-providers', [EWalletProviderController::class, 'index'])->name('bank.e-wallet-providers.index');
Route::get('e-wallet-providers/create', [EWalletProviderController::class, 'create'])->name('bank.e-wallet-providers.create');
Route::post('e-wallet-providers', [EWalletProviderController::class, 'store'])->name('bank.e-wallet-providers.store');
Route::get('e-wallet-providers/{provider}', [EWalletProviderController::class, 'show'])->name('bank.e-wallet-providers.show');
Route::get('e-wallet-providers/{provider}/edit', [EWalletProviderController::class, 'edit'])->name('bank.e-wallet-providers.edit');
Route::put('e-wallet-providers/{provider}', [EWalletProviderController::class, 'update'])->name('bank.e-wallet-providers.update');
Route::delete('e-wallet-providers/{provider}', [EWalletProviderController::class, 'destroy'])->name('bank.e-wallet-providers.destroy');

// Money ledgers: what is in the drawer, in the bank and on the wallet
Route::get('cash-ledger', [CashLedgerController::class, 'index'])->name('bank.cash-ledger.index');
Route::get('bank-ledger', [BankLedgerController::class, 'index'])->name('bank.bank-ledger.index');
Route::get('e-wallet-ledger', [EWalletLedgerController::class, 'index'])->name('bank.e-wallet-ledger.index');

// Receipts
Route::get('receipts', [ReceiptController::class, 'index'])->name('bank.receipts.index');
Route::get('receipts/create', [ReceiptController::class, 'create'])->name('bank.receipts.create');
Route::post('receipts', [ReceiptController::class, 'store'])->name('bank.receipts.store');
Route::put('receipts/{documentNumber}', [ReceiptController::class, 'update'])->name('bank.receipts.update');
Route::patch('receipts/{documentNumber}/approve', [ReceiptController::class, 'approve'])->name('bank.receipts.approve');
Route::post('receipts/batch-approve', [ReceiptController::class, 'batchApprove'])->name('bank.receipts.batch-approve');
Route::post('receipts/batch-cancel', [ReceiptController::class, 'batchCancel'])->name('bank.receipts.batch-cancel');
Route::get('receipts/invoices', [ReceiptController::class, 'invoiceOptions'])->name('bank.receipts.invoices');

Route::get('receipts/history', [ReceiptHistoryController::class, 'index'])->name('bank.receipts.history.index');
Route::get('receipts/history/export-excel', [ReceiptHistoryController::class, 'exportExcel'])->name('bank.receipts.history.export-excel');
Route::get('receipts/history/export-pdf', [ReceiptHistoryController::class, 'exportPdf'])->name('bank.receipts.history.export-pdf');
Route::get('receipts/history/{documentNumber}/pdf', [ReceiptHistoryController::class, 'pdf'])->name('bank.receipts.history.pdf');
Route::get('receipts/history/{documentNumber}', [ReceiptHistoryController::class, 'show'])->name('bank.receipts.history.show');

Route::get('receipts/cancelled', [ReceiptCancelledController::class, 'index'])->name('bank.receipts.cancelled.index');
Route::get('receipts/cancelled/export-pdf', [ReceiptCancelledController::class, 'exportPdf'])->name('bank.receipts.cancelled.export-pdf');
Route::patch('receipts/cancelled/{documentNumber}/uncancel', [ReceiptCancelledController::class, 'uncancel'])->name('bank.receipts.cancelled.uncancel');
Route::get('receipts/cancelled/{documentNumber}/pdf', [ReceiptCancelledController::class, 'pdf'])->name('bank.receipts.cancelled.pdf');
Route::get('receipts/cancelled/{documentNumber}', [ReceiptCancelledController::class, 'show'])->name('bank.receipts.cancelled.show');
Route::get('receipts/{documentNumber}/edit', [ReceiptController::class, 'edit'])->name('bank.receipts.edit');
Route::delete('receipts/{documentNumber}', [ReceiptController::class, 'destroy'])->name('bank.receipts.destroy');
Route::get('receipts/{documentNumber}', [ReceiptController::class, 'show'])->name('bank.receipts.show');

// Payment Vouchers
Route::get('payment-vouchers', [PaymentVoucherController::class, 'index'])->name('bank.payment-vouchers.index');
Route::get('payment-vouchers/create', [PaymentVoucherController::class, 'create'])->name('bank.payment-vouchers.create');
Route::post('payment-vouchers', [PaymentVoucherController::class, 'store'])->name('bank.payment-vouchers.store');
Route::put('payment-vouchers/{documentNumber}', [PaymentVoucherController::class, 'update'])->name('bank.payment-vouchers.update');
Route::patch('payment-vouchers/{documentNumber}/approve', [PaymentVoucherController::class, 'approve'])->name('bank.payment-vouchers.approve');
Route::post('payment-vouchers/batch-approve', [PaymentVoucherController::class, 'batchApprove'])->name('bank.payment-vouchers.batch-approve');
Route::post('payment-vouchers/batch-cancel', [PaymentVoucherController::class, 'batchCancel'])->name('bank.payment-vouchers.batch-cancel');
Route::get('payment-vouchers/invoices', [PaymentVoucherController::class, 'invoiceOptions'])->name('bank.payment-vouchers.invoices');

Route::get('payment-vouchers/history', [VoucherHistoryController::class, 'index'])->name('bank.payment-vouchers.history.index');
Route::get('payment-vouchers/history/export-excel', [VoucherHistoryController::class, 'exportExcel'])->name('bank.payment-vouchers.history.export-excel');
Route::get('payment-vouchers/history/export-pdf', [VoucherHistoryController::class, 'exportPdf'])->name('bank.payment-vouchers.history.export-pdf');
Route::get('payment-vouchers/history/{documentNumber}/pdf', [VoucherHistoryController::class, 'pdf'])->name('bank.payment-vouchers.history.pdf');
Route::get('payment-vouchers/history/{documentNumber}', [VoucherHistoryController::class, 'show'])->name('bank.payment-vouchers.history.show');

Route::get('payment-vouchers/cancelled', [VoucherCancelledController::class, 'index'])->name('bank.payment-vouchers.cancelled.index');
Route::get('payment-vouchers/cancelled/export-pdf', [VoucherCancelledController::class, 'exportPdf'])->name('bank.payment-vouchers.cancelled.export-pdf');
Route::patch('payment-vouchers/cancelled/{documentNumber}/uncancel', [VoucherCancelledController::class, 'uncancel'])->name('bank.payment-vouchers.cancelled.uncancel');
Route::get('payment-vouchers/cancelled/{documentNumber}/pdf', [VoucherCancelledController::class, 'pdf'])->name('bank.payment-vouchers.cancelled.pdf');
Route::get('payment-vouchers/cancelled/{documentNumber}', [VoucherCancelledController::class, 'show'])->name('bank.payment-vouchers.cancelled.show');
Route::get('payment-vouchers/{documentNumber}/edit', [PaymentVoucherController::class, 'edit'])->name('bank.payment-vouchers.edit');
Route::delete('payment-vouchers/{documentNumber}', [PaymentVoucherController::class, 'destroy'])->name('bank.payment-vouchers.destroy');
Route::get('payment-vouchers/{documentNumber}', [PaymentVoucherController::class, 'show'])->name('bank.payment-vouchers.show');

// Bank Journals
Route::get('bank-journals', [BankJournalController::class, 'index'])->name('bank.bank-journals.index');
Route::get('bank-journals/create', [BankJournalController::class, 'create'])->name('bank.bank-journals.create');
Route::post('bank-journals', [BankJournalController::class, 'store'])->name('bank.bank-journals.store');
Route::get('bank-journals/{documentNumber}/edit', [BankJournalController::class, 'edit'])->name('bank.bank-journals.edit');
Route::put('bank-journals/{documentNumber}', [BankJournalController::class, 'update'])->name('bank.bank-journals.update');
Route::patch('bank-journals/{documentNumber}/approve', [BankJournalController::class, 'approve'])->name('bank.bank-journals.approve');
Route::patch('bank-journals/{documentNumber}/cancel', [BankJournalController::class, 'cancel'])->name('bank.bank-journals.cancel');
Route::patch('bank-journals/{documentNumber}/restore', [BankJournalController::class, 'restore'])->name('bank.bank-journals.restore');
Route::delete('bank-journals/{documentNumber}', [BankJournalController::class, 'destroy'])->name('bank.bank-journals.destroy');
Route::get('bank-journals/{documentNumber}', [BankJournalController::class, 'show'])->name('bank.bank-journals.show');

// Customer Refunds
Route::get('customer-refunds', [CustomerRefundController::class, 'index'])->name('bank.customer-refunds.index');
Route::get('customer-refunds/create', [CustomerRefundController::class, 'create'])->name('bank.customer-refunds.create');
Route::post('customer-refunds', [CustomerRefundController::class, 'store'])->name('bank.customer-refunds.store');
Route::put('customer-refunds/{documentNumber}', [CustomerRefundController::class, 'update'])->name('bank.customer-refunds.update');
Route::patch('customer-refunds/{documentNumber}/approve', [CustomerRefundController::class, 'approve'])->name('bank.customer-refunds.approve');
Route::post('customer-refunds/batch-approve', [CustomerRefundController::class, 'batchApprove'])->name('bank.customer-refunds.batch-approve');
Route::post('customer-refunds/batch-cancel', [CustomerRefundController::class, 'batchCancel'])->name('bank.customer-refunds.batch-cancel');
Route::get('customer-refunds/invoices', [CustomerRefundController::class, 'invoiceOptions'])->name('bank.customer-refunds.invoices');

Route::get('customer-refunds/history', [CustomerHistoryController::class, 'index'])->name('bank.customer-refunds.history.index');
Route::get('customer-refunds/history/export-excel', [CustomerHistoryController::class, 'exportExcel'])->name('bank.customer-refunds.history.export-excel');
Route::get('customer-refunds/history/export-pdf', [CustomerHistoryController::class, 'exportPdf'])->name('bank.customer-refunds.history.export-pdf');
Route::get('customer-refunds/history/{documentNumber}/pdf', [CustomerHistoryController::class, 'pdf'])->name('bank.customer-refunds.history.pdf');
Route::get('customer-refunds/history/{documentNumber}', [CustomerHistoryController::class, 'show'])->name('bank.customer-refunds.history.show');

Route::get('customer-refunds/cancelled', [CustomerCancelledController::class, 'index'])->name('bank.customer-refunds.cancelled.index');
Route::get('customer-refunds/cancelled/export-pdf', [CustomerCancelledController::class, 'exportPdf'])->name('bank.customer-refunds.cancelled.export-pdf');
Route::patch('customer-refunds/cancelled/{documentNumber}/uncancel', [CustomerCancelledController::class, 'uncancel'])->name('bank.customer-refunds.cancelled.uncancel');
Route::get('customer-refunds/cancelled/{documentNumber}/pdf', [CustomerCancelledController::class, 'pdf'])->name('bank.customer-refunds.cancelled.pdf');
Route::get('customer-refunds/cancelled/{documentNumber}', [CustomerCancelledController::class, 'show'])->name('bank.customer-refunds.cancelled.show');
Route::get('customer-refunds/{documentNumber}/pdf', [CustomerRefundController::class, 'pdf'])->name('bank.customer-refunds.pdf');
Route::get('customer-refunds/{documentNumber}', [CustomerRefundController::class, 'show'])->name('bank.customer-refunds.show');

// Supplier Refunds
Route::get('supplier-refunds', [SupplierRefundController::class, 'index'])->name('bank.supplier-refunds.index');
Route::get('supplier-refunds/create', [SupplierRefundController::class, 'create'])->name('bank.supplier-refunds.create');
Route::post('supplier-refunds', [SupplierRefundController::class, 'store'])->name('bank.supplier-refunds.store');
Route::put('supplier-refunds/{documentNumber}', [SupplierRefundController::class, 'update'])->name('bank.supplier-refunds.update');
Route::patch('supplier-refunds/{documentNumber}/approve', [SupplierRefundController::class, 'approve'])->name('bank.supplier-refunds.approve');
Route::post('supplier-refunds/batch-approve', [SupplierRefundController::class, 'batchApprove'])->name('bank.supplier-refunds.batch-approve');
Route::post('supplier-refunds/batch-cancel', [SupplierRefundController::class, 'batchCancel'])->name('bank.supplier-refunds.batch-cancel');
Route::get('supplier-refunds/invoices', [SupplierRefundController::class, 'invoiceOptions'])->name('bank.supplier-refunds.invoices');

Route::get('supplier-refunds/history', [SupplierHistoryController::class, 'index'])->name('bank.supplier-refunds.history.index');
Route::get('supplier-refunds/history/export-excel', [SupplierHistoryController::class, 'exportExcel'])->name('bank.supplier-refunds.history.export-excel');
Route::get('supplier-refunds/history/export-pdf', [SupplierHistoryController::class, 'exportPdf'])->name('bank.supplier-refunds.history.export-pdf');
Route::get('supplier-refunds/history/{documentNumber}/pdf', [SupplierHistoryController::class, 'pdf'])->name('bank.supplier-refunds.history.pdf');
Route::get('supplier-refunds/history/{documentNumber}', [SupplierHistoryController::class, 'show'])->name('bank.supplier-refunds.history.show');

Route::get('supplier-refunds/cancelled', [SupplierCancelledController::class, 'index'])->name('bank.supplier-refunds.cancelled.index');
Route::get('supplier-refunds/cancelled/export-pdf', [SupplierCancelledController::class, 'exportPdf'])->name('bank.supplier-refunds.cancelled.export-pdf');
Route::patch('supplier-refunds/cancelled/{documentNumber}/uncancel', [SupplierCancelledController::class, 'uncancel'])->name('bank.supplier-refunds.cancelled.uncancel');
Route::get('supplier-refunds/cancelled/{documentNumber}/pdf', [SupplierCancelledController::class, 'pdf'])->name('bank.supplier-refunds.cancelled.pdf');
Route::get('supplier-refunds/cancelled/{documentNumber}', [SupplierCancelledController::class, 'show'])->name('bank.supplier-refunds.cancelled.show');
Route::get('supplier-refunds/{documentNumber}/pdf', [SupplierRefundController::class, 'pdf'])->name('bank.supplier-refunds.pdf');
Route::get('supplier-refunds/{documentNumber}', [SupplierRefundController::class, 'show'])->name('bank.supplier-refunds.show');
