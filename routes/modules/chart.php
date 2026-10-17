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

use App\Http\Controllers\ChartOfAccount\BalanceSheetController;
use App\Http\Controllers\ChartOfAccount\ChartController;
use App\Http\Controllers\ChartOfAccount\ChartReportController;
use App\Http\Controllers\ChartOfAccount\ChartReportDetailController;
use App\Http\Controllers\ChartOfAccount\ChartReportOutputController;
use App\Http\Controllers\ChartOfAccount\ChartSettingController;
use App\Http\Controllers\ChartOfAccount\ChartTypeController;
use App\Http\Controllers\ChartOfAccount\ProfitLossController;
use App\Http\Controllers\ChartOfAccount\TrialBalanceController;
use Illuminate\Support\Facades\Route;

// The General Ledger is immutable accounting history: reporting routes only.

Route::get('charts', [ChartController::class, 'index'])->name('chart-of-account.charts.index');
Route::get('charts/export-excel', [ChartController::class, 'exportExcel'])->name('chart-of-account.charts.export-excel');
Route::get('charts/create', [ChartController::class, 'create'])->name('chart-of-account.charts.create');
Route::post('charts', [ChartController::class, 'store'])->name('chart-of-account.charts.store');
Route::get('charts/{chart}/edit', [ChartController::class, 'edit'])->name('chart-of-account.charts.edit');
Route::put('charts/{chart}', [ChartController::class, 'update'])->name('chart-of-account.charts.update');
Route::delete('charts/{chart}', [ChartController::class, 'destroy'])->name('chart-of-account.charts.destroy');

Route::get('chart-types', [ChartTypeController::class, 'index'])->name('chart-of-account.chart-types.index');
Route::get('chart-types/create', [ChartTypeController::class, 'create'])->name('chart-of-account.chart-types.create');
Route::post('chart-types', [ChartTypeController::class, 'store'])->name('chart-of-account.chart-types.store');
Route::get('chart-types/{chartType}/edit', [ChartTypeController::class, 'edit'])->name('chart-of-account.chart-types.edit');
Route::put('chart-types/{chartType}', [ChartTypeController::class, 'update'])->name('chart-of-account.chart-types.update');
Route::delete('chart-types/{chartType}', [ChartTypeController::class, 'destroy'])->name('chart-of-account.chart-types.destroy');

Route::get('chart-settings', [ChartSettingController::class, 'index'])->name('chart-of-account.chart-settings.index');
Route::get('chart-settings/{chartSetting}/edit', [ChartSettingController::class, 'edit'])->name('chart-of-account.chart-settings.edit');
Route::put('chart-settings/{chartSetting}', [ChartSettingController::class, 'update'])->name('chart-of-account.chart-settings.update');

Route::get('chart-reports', [ChartReportController::class, 'index'])->name('chart-of-account.chart-reports.index');
Route::get('chart-reports/create', [ChartReportController::class, 'create'])->name('chart-of-account.chart-reports.create');
Route::post('chart-reports', [ChartReportController::class, 'store'])->name('chart-of-account.chart-reports.store');
Route::get('chart-reports/{chartReport}/output', [ChartReportOutputController::class, 'show'])->name('chart-of-account.chart-reports.output');
Route::get('chart-reports/{chartReport}/output/pdf', [ChartReportOutputController::class, 'exportPdf'])->name('chart-of-account.chart-reports.output.pdf');
Route::get('chart-reports/{chartReport}/output/excel', [ChartReportOutputController::class, 'exportExcel'])->name('chart-of-account.chart-reports.output.excel');
Route::get('chart-reports/{chartReport}/edit', [ChartReportController::class, 'edit'])->name('chart-of-account.chart-reports.edit');
Route::put('chart-reports/{chartReport}', [ChartReportController::class, 'update'])->name('chart-of-account.chart-reports.update');
Route::delete('chart-reports/{chartReport}', [ChartReportController::class, 'destroy'])->name('chart-of-account.chart-reports.destroy');

Route::get('chart-reports/{chartReport}/details/create', [ChartReportDetailController::class, 'create'])->name('chart-of-account.chart-reports.details.create');
Route::post('chart-reports/{chartReport}/details', [ChartReportDetailController::class, 'store'])->name('chart-of-account.chart-reports.details.store');
Route::get('chart-reports/{chartReport}/details/{chartReportDetail}/edit', [ChartReportDetailController::class, 'edit'])->name('chart-of-account.chart-reports.details.edit');
Route::put('chart-reports/{chartReport}/details/{chartReportDetail}', [ChartReportDetailController::class, 'update'])->name('chart-of-account.chart-reports.details.update');
Route::delete('chart-reports/{chartReport}/details/{chartReportDetail}', [ChartReportDetailController::class, 'destroy'])->name('chart-of-account.chart-reports.details.destroy');

Route::get('profit-loss', [ProfitLossController::class, 'index'])->name('chart-of-account.profit-loss.index');
Route::get('profit-loss/pdf', [ProfitLossController::class, 'exportPdf'])->name('chart-of-account.profit-loss.export-pdf');
Route::get('profit-loss/excel', [ProfitLossController::class, 'exportExcel'])->name('chart-of-account.profit-loss.export-excel');
Route::get('profit-loss/clean-pdf', [ProfitLossController::class, 'exportCleanPdf'])->name('chart-of-account.profit-loss.export-clean-pdf');

Route::get('balance-sheet', [BalanceSheetController::class, 'index'])->name('chart-of-account.balance-sheet.index');
Route::get('balance-sheet/pdf', [BalanceSheetController::class, 'exportPdf'])->name('chart-of-account.balance-sheet.export-pdf');
Route::get('balance-sheet/excel', [BalanceSheetController::class, 'exportExcel'])->name('chart-of-account.balance-sheet.export-excel');
Route::get('balance-sheet/clean-pdf', [BalanceSheetController::class, 'exportCleanPdf'])->name('chart-of-account.balance-sheet.export-clean-pdf');

Route::get('trial-balance', [TrialBalanceController::class, 'index'])->name('chart-of-account.trial-balance.index');
Route::get('trial-balance/pdf', [TrialBalanceController::class, 'exportPdf'])->name('chart-of-account.trial-balance.export-pdf');
Route::get('trial-balance/excel', [TrialBalanceController::class, 'exportExcel'])->name('chart-of-account.trial-balance.export-excel');
