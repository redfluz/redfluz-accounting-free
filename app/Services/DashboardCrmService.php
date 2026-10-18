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
use App\Models\System\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class DashboardCrmService
{
    /**
     * Get the CRM dashboard data for the selected month.
     *
     * @param  Carbon  $selectedMonth  The month to display data for.
     * @return array<string, mixed>
     */
    public function getDashboardData(Carbon $selectedMonth): array
    {
        $lastMonth = $selectedMonth->copy()->subMonth();
        $user = Auth::user();

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
            'userName' => $user instanceof User ? $user->name : null,
        ];
    }

    /**
     * Count posted invoices (purchase + sales) for a given month.
     *
     * @param  Carbon  $month  The month to count invoices for.
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
     * @param  Carbon  $month  The month to sum invoices for.
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
     * @param  Carbon  $month  The month to sum invoices for.
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
     * Sum AR payment amounts per month, keyed by `Y-m`.
     *
     * The sum comes back from SQL as a raw aggregate, so the shape of the row is
     * read defensively here and turned into a plain `Y-m => float` map that the
     * caller can read without guessing.
     *
     * @param  LedgerSubType  $subtype  The payment subtype to total.
     * @param  Carbon  $since  The earliest date to include.
     * @return array<string, float> The summed amount keyed by month.
     */
    private function sumArLedgerPaymentsByMonth(LedgerSubType $subtype, Carbon $since): array
    {
        $rows = ArLedger::query()
            ->selectRaw("strftime('%Y-%m', date) as month, SUM(debit_amount) as total")
            ->where('type', LedgerType::PAYMENT->value)
            ->where('subtype', $subtype->value)
            ->whereNotNull('date')
            ->where('date', '>=', $since)
            ->groupByRaw("strftime('%Y-%m', date)")
            ->get();

        // The query is grouped by month, so every month appears at most once.
        $totals = [];
        foreach ($rows as $row) {
            $month = $row->getAttribute('month');
            if (! is_string($month)) {
                continue;
            }

            $total = $row->getAttribute('total');
            $totals[$month] = is_numeric($total) ? (float) $total : 0.0;
        }

        return $totals;
    }

    /**
     * Get cash flow data for the last 12 months.
     *
     * @return Collection<int, array{month: string, label: string, inflow: float, outflow: float}>
     */
    private function getCashFlowLast12Months(): Collection
    {
        $today = now();

        $months = [];
        for ($i = 11; $i >= 0; $i--) {
            $month = $today->copy()->subMonths($i);
            $months[] = [
                'month' => $month->format('Y-m'),
                'label' => $month->format('M'),
            ];
        }

        // Receipts (inflow) and payment vouchers (outflow) for the same window.
        $since = $today->copy()->subMonths(11)->startOfMonth();
        $receipts = $this->sumArLedgerPaymentsByMonth(LedgerSubType::RECEIPT, $since);
        $payments = $this->sumArLedgerPaymentsByMonth(LedgerSubType::PAYMENT_VOUCHER, $since);

        return collect($months)->map(fn (array $row): array => [
            'month' => $row['month'],
            'label' => $row['label'],
            'inflow' => $receipts[$row['month']] ?? 0.0,
            'outflow' => $payments[$row['month']] ?? 0.0,
        ]);
    }
}
