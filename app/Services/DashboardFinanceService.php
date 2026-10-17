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
use App\Models\Finance\Ledger;
use App\Models\Inventory\Product;
use App\Models\Purchase\PurchaseInvoice;
use App\Models\Sales\SalesInvoice;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;

/**
 * DashboardFinanceService
 *
 * Uses your existing Eloquent models (App\Models\Apps\Crm\*) and the
 * LedgerStatus enum directly — no query-builder strings, no scopes
 * that don't exist on the model.
 *
 * Inject it in a controller:
 *   public function __construct(private DashboardFinanceService $dashboard) {}
 *
 * Or directly inside a Blade view:
 *
 *   Blade directive: @inject('dash', App\Services\DashboardFinanceService::class)
 */
class DashboardFinanceService
{
    /**
     * Get the finance summary.
     *
     * @return array<string, int|float> The resulting data.
     */
    public function getSummary(): array
    {
        return [
            'revenue_paid' => (float) Ledger::query()->where('status', LedgerStatus::POSTED)->sum('total_amount'),
            'revenue_pending' => (float) Ledger::query()->where('status', LedgerStatus::PENDING)->sum('total_amount'),
            'revenue_all_time' => (float) Ledger::query()->sum('total_amount'),
            'total_transactions' => Ledger::query()->count(),
            'total_companies' => Company::query()->count(),
            'total_contacts' => Contact::query()->count(),
            'total_products' => Product::query()->count(),
            'cancelled_count' => Ledger::query()->where('status', LedgerStatus::CANCELLED)->count(),
        ];
    }

    /**
     * Get monthly revenue series.
     *
     * @param  int  $months  The number of months.
     * @return array{labels: list<string>, data: list<float>} The resulting data.
     */
    public function getRevenueByMonth(int $months = 12): array
    {
        $rows = Ledger::query()->select(
            DB::raw("DATE_FORMAT(date, '%Y-%m') as period"),
            DB::raw('SUM(total_amount) as total')
        )
            ->where('date', '>=', now()->subMonths($months)->startOfMonth())
            ->groupBy('period')
            ->orderBy('period')
            ->pluck('total', 'period');

        $labels = [];
        $data = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $key = now()->subMonths($i)->format('Y-m');
            $labels[] = now()->subMonths($i)->format('M Y');
            $raw = $rows[$key] ?? 0;
            $data[] = is_numeric($raw) ? (float) $raw : 0.0;
        }

        return compact('labels', 'data');
    }

    /**
     * Get revenue grouped by status.
     *
     * @return array{labels: list<string>, totals: list<float>, counts: list<int|numeric-string>} The resulting data.
     */
    public function getRevenueByStatus(): array
    {
        // Group in PHP rather than SQL since `status` is enum-cast — grouping
        // by the raw column still works in the DB, but we map labels here to
        // stay consistent with the enum's cases rather than raw DB strings.
        $rows = Ledger::query()->select('status', DB::raw('SUM(total_amount) as total'), DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->get();

        return [
            'labels' => array_values($rows->map(fn (Ledger $r) => ucfirst($r->status->value))->values()->all()),
            'totals' => array_values($rows->pluck('total')->map(fn ($v) => is_numeric($v) ? (float) $v : 0.0)->values()->all()),
            'counts' => array_values($rows->pluck('count')->map(fn ($count): int => is_numeric($count) ? (int) $count : 0)->values()->all()),
        ];
    }

    /**
     * Get revenue by transaction type.
     *
     * @return array{labels: list<string>, data: list<float>} The resulting data.
     */
    public function getRevenueByTransactionType(): array
    {
        $rows = Ledger::query()->select(
            DB::raw("COALESCE(subtype, type, 'unspecified') as transaction_type"),
            DB::raw('SUM(total_amount) as total')
        )
            ->groupBy('transaction_type')
            ->orderByDesc('total')
            ->get();

        return [
            'labels' => array_values($rows->pluck('transaction_type')->map(fn ($t) => str_replace('_', ' ', ucfirst(is_string($t) ? $t : '')))->values()->all()),
            'data' => array_values($rows->pluck('total')->map(fn ($v) => is_numeric($v) ? (float) $v : 0.0)->values()->all()),
        ];
    }

    /**
     * Get the top products.
     *
     * @param  int  $limit  The maximum number of products.
     * @return array{labels: list<string>, revenue: list<float>, units: list<int|float|numeric-string>} The resulting data.
     */
    public function getTopProducts(int $limit = 5): array
    {
        $rows = Ledger::query()
            ->selectRaw('product_id, SUM(total_amount) as revenue, SUM(quantity) as units')
            ->with('product:id,name')
            ->groupBy('product_id')
            ->orderByDesc('revenue')
            ->limit($limit)
            ->get();

        return [
            'labels' => array_values($rows->map(fn (Ledger $r) => $r->product->name ?? '—')->values()->all()),
            'revenue' => array_values($rows->pluck('revenue')->map(fn ($v) => is_numeric($v) ? (float) $v : 0.0)->values()->all()),
            'units' => array_values($rows->pluck('units')->map(fn ($units): float => is_numeric($units) ? (float) $units : 0.0)->values()->all()),
        ];
    }

    /**
     * Get revenue by company.
     *
     * @return array{labels: list<string>, revenue: list<float>} The resulting data.
     */
    public function getRevenueByCompany(): array
    {
        $rows = Ledger::query()
            ->selectRaw('company_id, SUM(total_amount) as revenue')
            ->with('company:id,name')
            ->groupBy('company_id')
            ->orderByDesc('revenue')
            ->get();

        return [
            'labels' => array_values($rows->map(fn (Ledger $r) => $r->company->name ?? '—')->values()->all()),
            'revenue' => array_values($rows->pluck('revenue')->map(fn ($v) => is_numeric($v) ? (float) $v : 0.0)->values()->all()),
        ];
    }

    /**
     * Get recent ledger entries.
     *
     * @return Collection<int, Ledger> The resulting data.
     */
    public function getRecentLedgers(): Collection
    {
        return Ledger::query()
            ->with(['company:id,name', 'product:id,name', 'contact:id,name'])
            ->whereYear('date', now()->year)
            ->whereMonth('date', now()->month)
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->limit(5)
            ->get();
    }

    /**
     * Get the number of ledger entries recorded in the current month.
     */
    public function getCurrentMonthTransactionCount(): int
    {
        return Ledger::query()
            ->whereYear('date', now()->year)
            ->whereMonth('date', now()->month)
            ->count();
    }

    /**
     * Get the five largest outstanding supplier invoices.
     *
     * @param  int  $limit  The maximum number of results.
     * @return Collection<int, PurchaseInvoice>
     */
    public function getTopSupplierOutstanding(int $limit = 5): Collection
    {
        $paymentTotals = ArLedger::query()
            ->selectRaw('reference_number, SUM(debit_amount) as paid_amount')
            ->where('type', LedgerType::PAYMENT->value)
            ->where('subtype', LedgerSubType::PAYMENT_VOUCHER->value)
            ->whereNotNull('reference_number')
            ->groupBy('reference_number');

        return PurchaseInvoice::query()
            ->from('purchase_invoices as invoices')
            ->leftJoinSub($paymentTotals, 'payment_totals', fn (JoinClause $join) => $join
                ->on('payment_totals.reference_number', '=', 'invoices.document_number'))
            ->selectRaw(
                'invoices.document_number, invoices.company_name, '
                .'invoices.total_amount as invoice_amount, '
                .'COALESCE(payment_totals.paid_amount, 0) as paid_amount'
            )
            ->where('invoices.status', LedgerStatus::POSTED->value)
            ->whereRaw('invoices.total_amount - COALESCE(payment_totals.paid_amount, 0) > 0')
            ->orderByRaw('(invoices.total_amount - COALESCE(payment_totals.paid_amount, 0)) DESC')
            ->limit($limit)
            ->get()
            ->each(function (PurchaseInvoice $invoice): void {
                $invoiceAmount = (float) ($invoice->total_amount ?? 0);
                $paidRaw = $invoice->getAttribute('paid_amount') ?? 0;
                $paidAmount = is_numeric($paidRaw) ? (float) $paidRaw : 0.0;
                $invoice->setAttribute('balance_amount', max(0, $invoiceAmount - $paidAmount));
            });
    }

    /**
     * Get the five largest outstanding customer invoices.
     *
     * @param  int  $limit  The maximum number of results.
     * @return Collection<int, SalesInvoice>
     */
    public function getTopCustomerOutstanding(int $limit = 5): Collection
    {
        $receiptTotals = ArLedger::query()
            ->selectRaw('reference_number, SUM(debit_amount) as received_amount')
            ->where('type', LedgerType::PAYMENT->value)
            ->where('subtype', LedgerSubType::RECEIPT->value)
            ->whereNotNull('reference_number')
            ->groupBy('reference_number');

        return SalesInvoice::query()
            ->from('sales_invoices as invoices')
            ->leftJoinSub($receiptTotals, 'receipt_totals', fn (JoinClause $join) => $join
                ->on('receipt_totals.reference_number', '=', 'invoices.document_number'))
            ->selectRaw(
                'invoices.document_number, invoices.company_name, '
                .'invoices.total_amount as invoice_amount, '
                .'COALESCE(receipt_totals.received_amount, 0) as paid_amount'
            )
            ->where('invoices.status', LedgerStatus::POSTED->value)
            ->whereRaw('invoices.total_amount - COALESCE(receipt_totals.received_amount, 0) > 0')
            ->orderByRaw('(invoices.total_amount - COALESCE(receipt_totals.received_amount, 0)) DESC')
            ->limit($limit)
            ->get()
            ->each(function (SalesInvoice $invoice): void {
                $invoiceAmount = (float) ($invoice->total_amount ?? 0);
                $paidRaw = $invoice->getAttribute('paid_amount') ?? 0;
                $paidAmount = is_numeric($paidRaw) ? (float) $paidRaw : 0.0;
                $invoice->setAttribute('balance_amount', max(0, $invoiceAmount - $paidAmount));
            });
    }
}
