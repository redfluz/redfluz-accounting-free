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

namespace App\Services;

use App\Enums\LedgerStatus;
use App\Enums\LedgerSubType;
use App\Enums\LedgerType;
use App\Models\Crm\ArLedger;
use App\Models\Crm\Company;
use App\Models\Crm\Contact;
use App\Models\Purchase\PurchaseInvoice;
use App\Models\Sales\SalesInvoice;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class DashboardCrmService
{
    /**
     * Get the CRM dashboard data for the selected month.
     *
     * @param Carbon $selectedMonth The month to display data for.
     * @return array<string, mixed>
     */
    public function getDashboardData(Carbon $selectedMonth): array
    {
        $today = now();
        $lastMonth = $selectedMonth->copy()->subMonth();

        return [
            'companyCount' => Company::query()->count(),
            'lastMonthCompanyCount' => Company::query()->whereMonth('created_at', $lastMonth->month)->whereYear('created_at', $lastMonth->year)->count(),
            'contactCount' => Contact::query()->count(),
            'lastMonthContactCount' => Contact::query()->whereMonth('created_at', $lastMonth->month)->whereYear('created_at', $lastMonth->year)->count(),
            'supplierCount' => Company::query()->where('is_creditor', true)->count(),
            'lastMonthSupplierCount' => Company::query()->where('is_creditor', true)->whereMonth('created_at', $lastMonth->month)->whereYear('created_at', $lastMonth->year)->count(),
            'customerCount' => Company::query()->where('is_debtor', true)->count(),
            'lastMonthCustomerCount' => Company::query()->where('is_debtor', true)->whereMonth('created_at', $lastMonth->month)->whereYear('created_at', $lastMonth->year)->count(),
            'invoiceCount' => $this->getPostedInvoiceCount($selectedMonth),
            'lastMonthInvoiceCount' => $this->getPostedInvoiceCount($lastMonth),
            'payableTotal' => $this->getPostedPurchaseInvoiceTotal($selectedMonth),
            'receivableTotal' => $this->getPostedSalesInvoiceTotal($selectedMonth),
            'cashFlow' => $this->getCashFlowLast12Months(),
            'selectedMonth' => $selectedMonth,
            'userName' => Auth::user()?->name ?? null,
        ];
    }

    /**
     * Count posted invoices (purchase + sales) for a given month.
     *
     * @param Carbon $month The month to count invoices for.
     * @return int The number of posted invoices.
     */
    private function getPostedInvoiceCount(Carbon $month): int
    {
        $purchaseCount = PurchaseInvoice::query()
            ->where('status', LedgerStatus::POSTED->value)
            ->whereMonth('date', $month->month)
            ->whereYear('date', $month->year)
            ->count();

        $salesCount = SalesInvoice::query()
            ->where('status', LedgerStatus::POSTED->value)
            ->whereMonth('date', $month->month)
            ->whereYear('date', $month->year)
            ->count();

        return $purchaseCount + $salesCount;
    }

    /**
     * Get total posted purchase invoice amount for a given month.
     *
     * @param Carbon $month The month to sum invoices for.
     * @return float The total payable amount.
     */
    private function getPostedPurchaseInvoiceTotal(Carbon $month): float
    {
        return (float) PurchaseInvoice::query()
            ->where('status', LedgerStatus::POSTED->value)
            ->whereMonth('date', $month->month)
            ->whereYear('date', $month->year)
            ->sum('total_amount');
    }

    /**
     * Get total posted sales invoice amount for a given month.
     *
     * @param Carbon $month The month to sum invoices for.
     * @return float The total receivable amount.
     */
    private function getPostedSalesInvoiceTotal(Carbon $month): float
    {
        return (float) SalesInvoice::query()
            ->where('status', LedgerStatus::POSTED->value)
            ->whereMonth('date', $month->month)
            ->whereYear('date', $month->year)
            ->sum('total_amount');
    }

    /**
     * Get cash flow data for the last 12 months.
     *
     * @return Collection<int, array{month: string, label: string, inflow: float, outflow: float}>
     */
    private function getCashFlowLast12Months(): Collection
    {
        $today = now();
        $months = collect();

        for ($i = 11; $i >= 0; $i--) {
            $month = $today->copy()->subMonths($i);
            $months->push([
                'month' => $month->format('Y-m'),
                'label' => $month->format('M'),
                'inflow' => 0.0,
                'outflow' => 0.0,
            ]);
        }

        // Fetch receipts (inflow) grouped by month
        $receipts = ArLedger::query()
            ->selectRaw("strftime('%Y-%m', date) as month, SUM(debit_amount) as total")
            ->where('type', LedgerType::PAYMENT->value)
            ->where('subtype', LedgerSubType::RECEIPT->value)
            ->whereNotNull('date')
            ->where('date', '>=', $today->copy()->subMonths(11)->startOfMonth())
            ->groupByRaw("strftime('%Y-%m', date)")
            ->get()
            ->keyBy('month');

        // Fetch payment vouchers (outflow) grouped by month
        $payments = ArLedger::query()
            ->selectRaw("strftime('%Y-%m', date) as month, SUM(debit_amount) as total")
            ->where('type', LedgerType::PAYMENT->value)
            ->where('subtype', LedgerSubType::PAYMENT_VOUCHER->value)
            ->whereNotNull('date')
            ->where('date', '>=', $today->copy()->subMonths(11)->startOfMonth())
            ->groupByRaw("strftime('%Y-%m', date)")
            ->get()
            ->keyBy('month');

        return $months->map(function (array $row) use ($receipts, $payments): array {
            $receiptRow = $receipts->get($row['month']);
            $paymentRow = $payments->get($row['month']);

            return [
                'month' => $row['month'],
                'label' => $row['label'],
                'inflow' => (float) ($receiptRow?->total ?? 0),
                'outflow' => (float) ($paymentRow?->total ?? 0),
            ];
        });
    }
}
