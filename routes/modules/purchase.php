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

use App\Http\Controllers\Purchase\GoodsReceiptAdjustmentController;
use App\Http\Controllers\Purchase\GoodsReceiptController;
use App\Http\Controllers\Purchase\Invoice\CancelledController as InvoiceCancelledController;
use App\Http\Controllers\Purchase\Invoice\HistoryController as InvoiceHistoryController;
use App\Http\Controllers\Purchase\PurchaseAdjustment\CancelledController as PurchaseAdjustmentCancelledController;
use App\Http\Controllers\Purchase\PurchaseAdjustment\HistoryController as PurchaseAdjustmentHistoryController;
use App\Http\Controllers\Purchase\PurchaseAdjustmentController;
use App\Http\Controllers\Purchase\PurchaseInvoiceController;
use App\Http\Controllers\Purchase\PurchaseOrderController;
use App\Http\Controllers\Purchase\PurchaseOrderSettingController;
use App\Http\Controllers\Purchase\PurchaseReturnController;
use App\Http\Controllers\Purchase\SupplierOutstandingController;
use Illuminate\Support\Facades\Route;

Route::get('purchase-order-settings', [PurchaseOrderSettingController::class, 'index'])->name('purchase.purchase-order-settings.index');
Route::put('purchase-order-settings', [PurchaseOrderSettingController::class, 'update'])->name('purchase.purchase-order-settings.update');

Route::get('purchase-orders', [PurchaseOrderController::class, 'index'])->name('purchase.purchase-orders.index');
Route::get('purchase-orders/create', [PurchaseOrderController::class, 'create'])->name('purchase.purchase-orders.create');
Route::post('purchase-orders/companies', [PurchaseOrderController::class, 'storeCompany'])->name('purchase.purchase-orders.companies.store');
Route::post('purchase-orders', [PurchaseOrderController::class, 'store'])->name('purchase.purchase-orders.store');
Route::get('purchase-orders/{purchaseOrder}/edit', [PurchaseOrderController::class, 'edit'])->name('purchase.purchase-orders.edit');
Route::put('purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'update'])->name('purchase.purchase-orders.update');
Route::delete('purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'destroy'])->name('purchase.purchase-orders.destroy');
Route::patch('purchase-orders/{purchaseOrder}/approve', [PurchaseOrderController::class, 'approve'])->name('purchase.purchase-orders.approve');
Route::post('purchase-orders/{purchaseOrder}/purchase-invoice', [PurchaseOrderController::class, 'createPurchaseInvoice'])->name('purchase.purchase-orders.purchase-invoice');
Route::get('purchase-orders/{purchaseOrder}/pdf', [PurchaseOrderController::class, 'pdf'])->name('purchase.purchase-orders.pdf');
Route::get('purchase-orders/{purchaseOrder}/attachments/{attachment}', [PurchaseOrderController::class, 'downloadAttachment'])->name('purchase.purchase-orders.attachments.download');
Route::get('purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'show'])->name('purchase.purchase-orders.show');

Route::get('goods-receipt-adjustments', [GoodsReceiptAdjustmentController::class, 'index'])->name('purchase.goods-receipt-adjustments.index');
Route::get('goods-receipt-adjustments/history', [GoodsReceiptAdjustmentController::class, 'history'])->name('purchase.goods-receipt-adjustments.history.index');
Route::get('goods-receipt-adjustments/cancelled', [GoodsReceiptAdjustmentController::class, 'cancelled'])->name('purchase.goods-receipt-adjustments.cancelled.index');
Route::get('goods-receipt-adjustments/cancelled/{entryId}', [GoodsReceiptAdjustmentController::class, 'showCancelled'])->name('purchase.goods-receipt-adjustments.cancelled.show');
Route::patch('goods-receipt-adjustments/{documentNumber}/approve', [GoodsReceiptAdjustmentController::class, 'approveDocument'])->name('purchase.goods-receipt-adjustments.approve');
Route::patch('goods-receipt-adjustments/{entryId}/cancel', [GoodsReceiptAdjustmentController::class, 'cancel'])->name('purchase.goods-receipt-adjustments.cancel');
Route::patch('goods-receipt-adjustments/cancelled/{entryId}/uncancel', [GoodsReceiptAdjustmentController::class, 'uncancel'])->name('purchase.goods-receipt-adjustments.uncancel');
Route::get('goods-receipts', [GoodsReceiptController::class, 'index'])->name('purchase.goods-receipts.index');
Route::get('goods-receipts/create', [GoodsReceiptController::class, 'create'])->name('purchase.goods-receipts.create');
Route::post('goods-receipts', [GoodsReceiptController::class, 'store'])->name('purchase.goods-receipts.store');
Route::delete('goods-receipts/{documentNumber}', [GoodsReceiptController::class, 'destroy'])->name('purchase.goods-receipts.destroy');
Route::get('goods-receipts/{documentNumber}/edit', [GoodsReceiptController::class, 'edit'])->name('purchase.goods-receipts.edit');
Route::get('goods-receipts/{documentNumber}/adjust', [GoodsReceiptAdjustmentController::class, 'create'])->name('purchase.goods-receipts.adjust');
Route::post('goods-receipts/{documentNumber}/adjust', [GoodsReceiptAdjustmentController::class, 'store'])->name('purchase.goods-receipts.adjust.store');
Route::patch('goods-receipts/{documentNumber}/adjust/approve', [GoodsReceiptAdjustmentController::class, 'approve'])->name('purchase.goods-receipts.adjust.approve');
Route::delete('goods-receipts/{documentNumber}/adjust/{entryId}', [GoodsReceiptAdjustmentController::class, 'destroy'])->name('purchase.goods-receipts.adjust.destroy');
Route::put('goods-receipts/{documentNumber}', [GoodsReceiptController::class, 'update'])->name('purchase.goods-receipts.update');
Route::patch('goods-receipts/{documentNumber}/approve', [GoodsReceiptController::class, 'approve'])->name('purchase.goods-receipts.approve');
Route::get('goods-receipts/{documentNumber}/attachments/{attachment}', [GoodsReceiptController::class, 'downloadAttachment'])->name('purchase.goods-receipts.attachments.download');
Route::get('goods-receipts/{documentNumber}', [GoodsReceiptController::class, 'show'])->name('purchase.goods-receipts.show');

Route::get('purchase-invoices', [PurchaseInvoiceController::class, 'index'])->name('purchase.invoices.index');
Route::get('purchase-invoices/cancelled', [InvoiceCancelledController::class, 'index'])->name('purchase.invoices.cancelled.index');
Route::get('purchase-invoices/cancelled/export-excel', [InvoiceCancelledController::class, 'exportExcel'])->name('purchase.invoices.cancelled.export-excel');
Route::get('purchase-invoices/cancelled/export-pdf', [InvoiceCancelledController::class, 'exportPdf'])->name('purchase.invoices.cancelled.export-pdf');
Route::get('purchase-invoices/history', [InvoiceHistoryController::class, 'index'])->name('purchase.invoices.history.index');
Route::get('purchase-invoices/history/export-excel', [InvoiceHistoryController::class, 'exportExcel'])->name('purchase.invoices.history.export-excel');
Route::get('purchase-invoices/history/export-pdf', [InvoiceHistoryController::class, 'exportPdf'])->name('purchase.invoices.history.export-pdf');
Route::get('purchase-invoices/history/{documentNumber}', [InvoiceHistoryController::class, 'show'])->name('purchase.invoices.history.show');
Route::get('purchase-invoices/history/{documentNumber}/clone', [InvoiceHistoryController::class, 'clone'])->name('purchase.invoices.history.clone');
Route::patch('purchase-invoices/cancelled/{documentNumber}/uncancel', [InvoiceCancelledController::class, 'uncancel'])->name('purchase.invoices.cancelled.uncancel');
Route::get('purchase-invoices/cancelled/{documentNumber}', [InvoiceCancelledController::class, 'show'])->name('purchase.invoices.cancelled.show');
Route::get('supplier-outstanding', [SupplierOutstandingController::class, 'index'])->name('purchase.supplier-outstanding.index');
Route::get('supplier-outstanding/export-excel', [SupplierOutstandingController::class, 'exportExcel'])->name('purchase.supplier-outstanding.export-excel');
Route::get('supplier-outstanding/export-pdf', [SupplierOutstandingController::class, 'exportPdf'])->name('purchase.supplier-outstanding.export-pdf');
Route::get('purchase-invoices/create', [PurchaseInvoiceController::class, 'create'])->name('purchase.invoices.create');
Route::post('purchase-invoices/companies', [PurchaseInvoiceController::class, 'storeCompany'])->name('purchase.invoices.companies.store');
Route::post('purchase-invoices', [PurchaseInvoiceController::class, 'store'])->name('purchase.invoices.store');
Route::get('purchase-invoices/{documentNumber}/edit', [PurchaseInvoiceController::class, 'edit'])->name('purchase.invoices.edit');
Route::put('purchase-invoices/{documentNumber}', [PurchaseInvoiceController::class, 'update'])->name('purchase.invoices.update');
Route::delete('purchase-invoices/{documentNumber}', [PurchaseInvoiceController::class, 'destroy'])->name('purchase.invoices.destroy');
Route::patch('purchase-invoices/{documentNumber}/approve', [PurchaseInvoiceController::class, 'approve'])->name('purchase.invoices.approve');
Route::patch('purchase-invoices/{documentNumber}/reverse', [PurchaseInvoiceController::class, 'reverse'])->name('purchase.invoices.reverse');
Route::post('purchase-invoices/batch-approve', [PurchaseInvoiceController::class, 'batchApprove'])->name('purchase.invoices.batch-approve');
Route::post('purchase-invoices/batch-cancel', [PurchaseInvoiceController::class, 'batchCancel'])->name('purchase.invoices.batch-cancel');
Route::get('purchase-invoices/payments/{paymentDocumentNumber}/pdf', [PurchaseInvoiceController::class, 'paymentPdf'])->name('purchase.invoices.payments.pdf');
Route::get('purchase-invoices/{documentNumber}/pdf', [PurchaseInvoiceController::class, 'pdf'])->name('purchase.invoices.pdf');
Route::get('purchase-invoices/{documentNumber}', [PurchaseInvoiceController::class, 'show'])->name('purchase.invoices.show');

Route::get('purchase-adjustments', [PurchaseAdjustmentController::class, 'index'])->name('purchase.adjustments.index');
Route::get('purchase-adjustments/cancelled', [PurchaseAdjustmentCancelledController::class, 'index'])->name('purchase.adjustments.cancelled.index');
Route::get('purchase-adjustments/cancelled/export-excel', [PurchaseAdjustmentCancelledController::class, 'exportExcel'])->name('purchase.adjustments.cancelled.export-excel');
Route::get('purchase-adjustments/cancelled/export-pdf', [PurchaseAdjustmentCancelledController::class, 'exportPdf'])->name('purchase.adjustments.cancelled.export-pdf');
Route::get('purchase-adjustments/history', [PurchaseAdjustmentHistoryController::class, 'index'])->name('purchase.adjustments.history.index');
Route::get('purchase-adjustments/history/export-excel', [PurchaseAdjustmentHistoryController::class, 'exportExcel'])->name('purchase.adjustments.history.export-excel');
Route::get('purchase-adjustments/history/export-pdf', [PurchaseAdjustmentHistoryController::class, 'exportPdf'])->name('purchase.adjustments.history.export-pdf');
Route::get('purchase-adjustments/history/{documentNumber}', [PurchaseAdjustmentHistoryController::class, 'show'])->name('purchase.adjustments.history.show');
Route::patch('purchase-adjustments/cancelled/{documentNumber}/uncancel', [PurchaseAdjustmentCancelledController::class, 'uncancel'])->name('purchase.adjustments.cancelled.uncancel');
Route::get('purchase-adjustments/cancelled/{documentNumber}', [PurchaseAdjustmentCancelledController::class, 'show'])->name('purchase.adjustments.cancelled.show');
Route::get('purchase-adjustments/create', [PurchaseAdjustmentController::class, 'create'])->name('purchase.adjustments.create');
Route::get('purchase-adjustments/invoices', [PurchaseAdjustmentController::class, 'invoiceOptions'])->name('purchase.adjustments.invoices');
Route::post('purchase-adjustments', [PurchaseAdjustmentController::class, 'store'])->name('purchase.adjustments.store');
Route::put('purchase-adjustments/{documentNumber}', [PurchaseAdjustmentController::class, 'update'])->name('purchase.adjustments.update');
Route::patch('purchase-adjustments/{documentNumber}/approve', [PurchaseAdjustmentController::class, 'approve'])->name('purchase.adjustments.approve');
Route::patch('purchase-adjustments/{documentNumber}/reverse', [PurchaseAdjustmentController::class, 'reverse'])->name('purchase.adjustments.reverse');
Route::post('purchase-adjustments/batch-approve', [PurchaseAdjustmentController::class, 'batchApprove'])->name('purchase.adjustments.batch-approve');
Route::post('purchase-adjustments/batch-cancel', [PurchaseAdjustmentController::class, 'batchCancel'])->name('purchase.adjustments.batch-cancel');
Route::get('purchase-adjustments/{documentNumber}/pdf', [PurchaseAdjustmentController::class, 'pdf'])->name('purchase.adjustments.pdf');
Route::get('purchase-adjustments/{documentNumber}', [PurchaseAdjustmentController::class, 'show'])->name('purchase.adjustments.show');

Route::get('purchase-returns', [PurchaseReturnController::class, 'index'])->name('purchase.returns.index');
Route::get('purchase-returns/create', [PurchaseReturnController::class, 'create'])->name('purchase.returns.create');
Route::post('purchase-returns', [PurchaseReturnController::class, 'store'])->name('purchase.returns.store');
Route::patch('purchase-returns/{documentNumber}/approve', [PurchaseReturnController::class, 'approve'])->name('purchase.returns.approve');
Route::post('purchase-returns/batch-approve', [PurchaseReturnController::class, 'batchApprove'])->name('purchase.returns.batch-approve');
Route::post('purchase-returns/batch-cancel', [PurchaseReturnController::class, 'batchCancel'])->name('purchase.returns.batch-cancel');
Route::delete('purchase-returns/{documentNumber}', [PurchaseReturnController::class, 'destroy'])->name('purchase.returns.destroy');
Route::get('purchase-returns/{documentNumber}', [PurchaseReturnController::class, 'show'])->name('purchase.returns.show');
