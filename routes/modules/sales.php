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
use App\Http\Controllers\Sales\QuotationController;
use App\Http\Controllers\Sales\SalesAdjustment\CancelledController as SalesAdjustmentCancelledController;
use App\Http\Controllers\Sales\SalesAdjustment\HistoryController as SalesAdjustmentHistoryController;
use App\Http\Controllers\Sales\SalesAdjustmentController;
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
Route::post('sales-invoices/{documentNumber}/receive-return', [SalesInvoiceController::class, 'receiveReturn'])->name('sales.invoices.receive-return');
Route::get('sales-invoices/payments/{paymentDocumentNumber}/pdf', [SalesInvoiceController::class, 'paymentPdf'])->name('sales.invoices.payments.pdf');
Route::get('sales-invoices/{documentNumber}/pdf', [SalesInvoiceController::class, 'pdf'])->name('sales.invoices.pdf');
Route::get('sales-invoices/{documentNumber}', [SalesInvoiceController::class, 'show'])->name('sales.invoices.show');

Route::get('customer-outstanding', [CustomerOutstandingController::class, 'index'])->name('sales.customer-outstanding.index');
Route::get('customer-outstanding/export-excel', [CustomerOutstandingController::class, 'exportExcel'])->name('sales.customer-outstanding.export-excel');
Route::get('customer-outstanding/export-pdf', [CustomerOutstandingController::class, 'exportPdf'])->name('sales.customer-outstanding.export-pdf');

Route::get('sales-adjustments', [SalesAdjustmentController::class, 'index'])->name('sales.adjustments.index');
Route::get('sales-adjustments/cancelled', [SalesAdjustmentCancelledController::class, 'index'])->name('sales.adjustments.cancelled.index');
Route::get('sales-adjustments/cancelled/export-excel', [SalesAdjustmentCancelledController::class, 'exportExcel'])->name('sales.adjustments.cancelled.export-excel');
Route::get('sales-adjustments/cancelled/export-pdf', [SalesAdjustmentCancelledController::class, 'exportPdf'])->name('sales.adjustments.cancelled.export-pdf');
Route::get('sales-adjustments/history', [SalesAdjustmentHistoryController::class, 'index'])->name('sales.adjustments.history.index');
Route::get('sales-adjustments/history/export-excel', [SalesAdjustmentHistoryController::class, 'exportExcel'])->name('sales.adjustments.history.export-excel');
Route::get('sales-adjustments/history/export-pdf', [SalesAdjustmentHistoryController::class, 'exportPdf'])->name('sales.adjustments.history.export-pdf');
Route::get('sales-adjustments/history/{documentNumber}', [SalesAdjustmentHistoryController::class, 'show'])->name('sales.adjustments.history.show');
Route::patch('sales-adjustments/cancelled/{documentNumber}/uncancel', [SalesAdjustmentCancelledController::class, 'uncancel'])->name('sales.adjustments.cancelled.uncancel');
Route::get('sales-adjustments/cancelled/{documentNumber}', [SalesAdjustmentCancelledController::class, 'show'])->name('sales.adjustments.cancelled.show');
Route::get('sales-adjustments/create', [SalesAdjustmentController::class, 'create'])->name('sales.adjustments.create');
Route::get('sales-adjustments/invoices', [SalesAdjustmentController::class, 'invoiceOptions'])->name('sales.adjustments.invoices');
Route::post('sales-adjustments', [SalesAdjustmentController::class, 'store'])->name('sales.adjustments.store');
Route::put('sales-adjustments/{documentNumber}', [SalesAdjustmentController::class, 'update'])->name('sales.adjustments.update');
Route::patch('sales-adjustments/{documentNumber}/approve', [SalesAdjustmentController::class, 'approve'])->name('sales.adjustments.approve');
Route::patch('sales-adjustments/{documentNumber}/reverse', [SalesAdjustmentController::class, 'reverse'])->name('sales.adjustments.reverse');
Route::post('sales-adjustments/batch-approve', [SalesAdjustmentController::class, 'batchApprove'])->name('sales.adjustments.batch-approve');
Route::post('sales-adjustments/batch-cancel', [SalesAdjustmentController::class, 'batchCancel'])->name('sales.adjustments.batch-cancel');
Route::get('sales-adjustments/{documentNumber}/pdf', [SalesAdjustmentController::class, 'pdf'])->name('sales.adjustments.pdf');
Route::get('sales-adjustments/{documentNumber}', [SalesAdjustmentController::class, 'show'])->name('sales.adjustments.show');
