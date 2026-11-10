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

use App\Http\Controllers\Sales\CustomerOutstandingController;
use App\Http\Controllers\Sales\DeliveryOrderController;
use App\Http\Controllers\Sales\Invoice\CancelledController as InvoiceCancelledController;
use App\Http\Controllers\Sales\Invoice\HistoryController as InvoiceHistoryController;
use App\Http\Controllers\Sales\ManualAdjustment\CancelledController;
use App\Http\Controllers\Sales\ManualAdjustment\HistoryController;
use App\Http\Controllers\Sales\ManualSalesAdjustmentController;
use App\Http\Controllers\Sales\QuotationController;
use App\Http\Controllers\Sales\SalesInvoiceController;
use App\Http\Controllers\Sales\SalesOrderController;
use Illuminate\Support\Facades\Route;

Route::get('quotations', [QuotationController::class, 'index'])->name('sales.quotations.index');
Route::get('quotations/create', [QuotationController::class, 'create'])->name('sales.quotations.create');
Route::post('quotations/companies', [QuotationController::class, 'storeCompany'])->name('sales.quotations.companies.store');
Route::post('quotations', [QuotationController::class, 'store'])->name('sales.quotations.store');
Route::get('quotations/{quotationNumber}/edit', [QuotationController::class, 'edit'])->name('sales.quotations.edit');
Route::put('quotations/{quotationNumber}', [QuotationController::class, 'update'])->name('sales.quotations.update');
Route::patch('quotations/{quotationNumber}/convert', [QuotationController::class, 'convert'])->name('sales.quotations.convert');
Route::get('quotations/{quotationNumber}/pdf', [QuotationController::class, 'pdf'])->name('sales.quotations.pdf');
Route::get('quotations/{quotationNumber}', [QuotationController::class, 'show'])->name('sales.quotations.show');

Route::get('sales-orders', [SalesOrderController::class, 'index'])->name('sales.sales-orders.index');
Route::get('sales-orders/create', [SalesOrderController::class, 'create'])->name('sales.sales-orders.create');
Route::post('sales-orders/companies', [SalesOrderController::class, 'storeCompany'])->name('sales.sales-orders.companies.store');
Route::post('sales-orders', [SalesOrderController::class, 'store'])->name('sales.sales-orders.store');
Route::get('sales-orders/{salesOrder}/edit', [SalesOrderController::class, 'edit'])->name('sales.sales-orders.edit');
Route::put('sales-orders/{salesOrder}', [SalesOrderController::class, 'update'])->name('sales.sales-orders.update');
Route::delete('sales-orders/{salesOrder}', [SalesOrderController::class, 'destroy'])->name('sales.sales-orders.destroy');
Route::patch('sales-orders/{salesOrder}/approve', [SalesOrderController::class, 'approve'])->name('sales.sales-orders.approve');
Route::post('sales-orders/{salesOrder}/sales-invoice', [SalesOrderController::class, 'createSalesInvoice'])->name('sales.sales-orders.sales-invoice');
Route::get('sales-orders/{salesOrder}/attachment', [SalesOrderController::class, 'downloadAttachment'])->name('sales.sales-orders.attachment');
Route::get('sales-orders/{salesOrder}/pdf', [SalesOrderController::class, 'pdf'])->name('sales.sales-orders.pdf');
Route::get('sales-orders/{salesOrder}', [SalesOrderController::class, 'show'])->name('sales.sales-orders.show');

Route::get('delivery-orders', [DeliveryOrderController::class, 'index'])->name('sales.delivery-orders.index');
Route::get('delivery-orders/create', [DeliveryOrderController::class, 'create'])->name('sales.delivery-orders.create');
Route::post('delivery-orders', [DeliveryOrderController::class, 'store'])->name('sales.delivery-orders.store');
Route::get('delivery-orders/{deliveryOrder}/edit', [DeliveryOrderController::class, 'edit'])->name('sales.delivery-orders.edit');
Route::put('delivery-orders/{deliveryOrder}', [DeliveryOrderController::class, 'update'])->name('sales.delivery-orders.update');
Route::delete('delivery-orders/{deliveryOrder}', [DeliveryOrderController::class, 'destroy'])->name('sales.delivery-orders.destroy');
Route::patch('delivery-orders/{deliveryOrder}/post', [DeliveryOrderController::class, 'post'])->name('sales.delivery-orders.post');
Route::post('delivery-orders/{deliveryOrder}/sales-invoice', [DeliveryOrderController::class, 'createSalesInvoice'])->name('sales.delivery-orders.sales-invoice');
Route::get('delivery-orders/{deliveryOrder}/pdf', [DeliveryOrderController::class, 'pdf'])->name('sales.delivery-orders.pdf');
Route::get('delivery-orders/{deliveryOrder}', [DeliveryOrderController::class, 'show'])->name('sales.delivery-orders.show');

Route::get('sales-invoices', [SalesInvoiceController::class, 'index'])->name('sales.invoices.index');
Route::get('sales-invoices/cancelled', [InvoiceCancelledController::class, 'index'])->name('sales.invoices.cancelled.index');
Route::get('sales-invoices/cancelled/export-excel', [InvoiceCancelledController::class, 'exportExcel'])->name('sales.invoices.cancelled.export-excel');
Route::get('sales-invoices/cancelled/export-pdf', [InvoiceCancelledController::class, 'exportPdf'])->name('sales.invoices.cancelled.export-pdf');
Route::get('sales-invoices/history', [InvoiceHistoryController::class, 'index'])->name('sales.invoices.history.index');
Route::get('sales-invoices/history/export-excel', [InvoiceHistoryController::class, 'exportExcel'])->name('sales.invoices.history.export-excel');
Route::get('sales-invoices/history/export-pdf', [InvoiceHistoryController::class, 'exportPdf'])->name('sales.invoices.history.export-pdf');
Route::get('sales-invoices/history/{documentNumber}', [InvoiceHistoryController::class, 'show'])->name('sales.invoices.history.show');
Route::get('sales-invoices/history/{documentNumber}/clone', [InvoiceHistoryController::class, 'clone'])->name('sales.invoices.history.clone');
Route::patch('sales-invoices/cancelled/{documentNumber}/uncancel', [InvoiceCancelledController::class, 'uncancel'])->name('sales.invoices.cancelled.uncancel');
Route::get('sales-invoices/cancelled/{documentNumber}', [InvoiceCancelledController::class, 'show'])->name('sales.invoices.cancelled.show');
Route::get('sales-invoices/create', [SalesInvoiceController::class, 'create'])->name('sales.invoices.create');
Route::post('sales-invoices/companies', [SalesInvoiceController::class, 'storeCompany'])->name('sales.invoices.companies.store');
Route::post('sales-invoices', [SalesInvoiceController::class, 'store'])->name('sales.invoices.store');
Route::get('sales-invoices/{documentNumber}/edit', [SalesInvoiceController::class, 'edit'])->name('sales.invoices.edit');
Route::put('sales-invoices/{documentNumber}', [SalesInvoiceController::class, 'update'])->name('sales.invoices.update');
Route::delete('sales-invoices/{documentNumber}', [SalesInvoiceController::class, 'destroy'])->name('sales.invoices.destroy');
Route::patch('sales-invoices/{documentNumber}/approve', [SalesInvoiceController::class, 'approve'])->name('sales.invoices.approve');
Route::patch('sales-invoices/{documentNumber}/reverse', [SalesInvoiceController::class, 'reverse'])->name('sales.invoices.reverse');
Route::post('sales-invoices/batch-approve', [SalesInvoiceController::class, 'batchApprove'])->name('sales.invoices.batch-approve');
Route::post('sales-invoices/batch-cancel', [SalesInvoiceController::class, 'batchCancel'])->name('sales.invoices.batch-cancel');
Route::post('sales-invoices/{documentNumber}/deduct-inventory', [SalesInvoiceController::class, 'deductInventory'])->name('sales.invoices.deduct-inventory');
Route::get('sales-invoices/payments/{paymentDocumentNumber}/pdf', [SalesInvoiceController::class, 'paymentPdf'])->name('sales.invoices.payments.pdf');
Route::get('sales-invoices/{documentNumber}/pdf', [SalesInvoiceController::class, 'pdf'])->name('sales.invoices.pdf');
Route::get('sales-invoices/{documentNumber}', [SalesInvoiceController::class, 'show'])->name('sales.invoices.show');

Route::get('customer-outstanding', [CustomerOutstandingController::class, 'index'])->name('sales.customer-outstanding.index');
Route::get('customer-outstanding/export-excel', [CustomerOutstandingController::class, 'exportExcel'])->name('sales.customer-outstanding.export-excel');
Route::get('customer-outstanding/export-pdf', [CustomerOutstandingController::class, 'exportPdf'])->name('sales.customer-outstanding.export-pdf');

// Manual Sales Adjustments (COA-based debit/credit notes)
Route::get('manual-sales-adjustments', [ManualSalesAdjustmentController::class, 'index'])->name('sales.manual-adjustments.index');
Route::get('manual-sales-adjustments/cancelled', [CancelledController::class, 'index'])->name('sales.manual-adjustments.cancelled.index');
Route::get('manual-sales-adjustments/cancelled/export-excel', [CancelledController::class, 'exportExcel'])->name('sales.manual-adjustments.cancelled.export-excel');
Route::get('manual-sales-adjustments/cancelled/export-pdf', [CancelledController::class, 'exportPdf'])->name('sales.manual-adjustments.cancelled.export-pdf');
Route::get('manual-sales-adjustments/history', [HistoryController::class, 'index'])->name('sales.manual-adjustments.history.index');
Route::get('manual-sales-adjustments/history/export-excel', [HistoryController::class, 'exportExcel'])->name('sales.manual-adjustments.history.export-excel');
Route::get('manual-sales-adjustments/history/export-pdf', [HistoryController::class, 'exportPdf'])->name('sales.manual-adjustments.history.export-pdf');
Route::get('manual-sales-adjustments/history/{documentNumber}', [HistoryController::class, 'show'])->name('sales.manual-adjustments.history.show');
Route::patch('manual-sales-adjustments/cancelled/{documentNumber}/uncancel', [CancelledController::class, 'uncancel'])->name('sales.manual-adjustments.cancelled.uncancel');
Route::get('manual-sales-adjustments/cancelled/{documentNumber}', [CancelledController::class, 'show'])->name('sales.manual-adjustments.cancelled.show');
Route::get('manual-sales-adjustments/create', [ManualSalesAdjustmentController::class, 'create'])->name('sales.manual-adjustments.create');
Route::get('manual-sales-adjustments/charts/search', [ManualSalesAdjustmentController::class, 'searchCharts'])->name('sales.manual-adjustments.charts.search');
Route::get('manual-sales-adjustments/invoices', [ManualSalesAdjustmentController::class, 'invoiceOptions'])->name('sales.manual-adjustments.invoices');
Route::get('manual-sales-adjustments/{documentNumber}/edit', [ManualSalesAdjustmentController::class, 'edit'])->name('sales.manual-adjustments.edit');
Route::post('manual-sales-adjustments', [ManualSalesAdjustmentController::class, 'store'])->name('sales.manual-adjustments.store');
Route::put('manual-sales-adjustments/{documentNumber}', [ManualSalesAdjustmentController::class, 'update'])->name('sales.manual-adjustments.update');
Route::delete('manual-sales-adjustments/{documentNumber}', [ManualSalesAdjustmentController::class, 'destroy'])->name('sales.manual-adjustments.destroy');
Route::patch('manual-sales-adjustments/{documentNumber}/approve', [ManualSalesAdjustmentController::class, 'approve'])->name('sales.manual-adjustments.approve');
Route::patch('manual-sales-adjustments/{documentNumber}/reverse', [ManualSalesAdjustmentController::class, 'reverse'])->name('sales.manual-adjustments.reverse');
Route::post('manual-sales-adjustments/batch-approve', [ManualSalesAdjustmentController::class, 'batchApprove'])->name('sales.manual-adjustments.batch-approve');
Route::post('manual-sales-adjustments/batch-cancel', [ManualSalesAdjustmentController::class, 'batchCancel'])->name('sales.manual-adjustments.batch-cancel');
Route::get('manual-sales-adjustments/{documentNumber}', [ManualSalesAdjustmentController::class, 'show'])->name('sales.manual-adjustments.show');
