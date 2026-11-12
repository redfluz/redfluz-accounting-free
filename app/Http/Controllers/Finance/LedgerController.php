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

namespace App\Http\Controllers\Finance;

use App\Enums\ChartOfAccount\ChartReportGroupEnum;
use App\Enums\FileResources;
use App\Http\Controllers\Controller;
use App\Models\ChartOfAccount\Chart;
use App\Models\Finance\Ledger;
use App\Support\Traits\ResolvesLedgerSources;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

use function is_string;

/**
 * Provide read-only General Ledger reporting.
 *
 * Ledger rows are immutable accounting history. Corrections must be performed
 * through the originating document workflow or a journal reversal so the audit
 * trail contains both the original posting and its correcting transaction.
 */
class LedgerController extends Controller
{
    use ResolvesLedgerSources;

    /**
     * Build the filtered query for General Ledger reporting.
     *
     * @param  Request  $request  The HTTP request instance.
     * @return Builder<Ledger> Returns the Eloquent query builder.
     */
    private function buildFilteredQuery(Request $request): Builder
    {
        /** @var string|null $search */
        $search = $request->input('search');
        /** @var string|null $startDate */
        $startDate = $request->input('start_date');
        /** @var string|null $endDate */
        $endDate = $request->input('end_date');
        /** @var string|null $type */
        $type = $request->input('type');
        $account = $request->string('account')->trim()->toString();

        $allowedSortColumns = [
            'document_number' => 'document_number',
            'reference_number' => 'reference_number',
            'date' => 'date',
            'type' => 'type',
            'debit_amount' => 'debit_amount',
            'credit_amount' => 'credit_amount',
        ];

        /** @var string|null $sortBy */
        $sortBy = $request->input('sort_by');
        /** @var string $orderInput */
        $orderInput = $request->input('order') ?? '';
        $order = strtolower($orderInput) === 'asc' ? 'asc' : 'desc';

        $query = Ledger::query()
            ->with(['company', 'chart'])
            ->when(is_string($type) && $type !== '', fn ($q) => $q->where('type', $type))
            ->when($account !== '', function (Builder $query) use ($account): void {
                $query->where('chart_code', $account);
            })
            ->when(
                $request->boolean('exclude_year_end_closing'),
                fn (Builder $query) => $query->where('document_number', 'not like', 'YEC%')
            )
            ->when(is_string($search) && $search !== '', function ($q) use ($search) {
                $q->where(function ($sq) use ($search) {
                    $sq->where('reference_number', 'like', "%$search%")
                        ->orWhere('document_number', 'like', "%$search%")
                        ->orWhere('chart_name', 'like', "%$search%")
                        ->orWhereHas('company', fn ($cq) => $cq->where('name', 'like', "%$search%"));
                });
            })
            ->when(is_string($startDate) && $startDate !== '', fn ($q) => $q->whereDate('date', '>=', $startDate))
            ->when(is_string($endDate) && $endDate !== '', fn ($q) => $q->whereDate('date', '<=', $endDate));

        if ($request->boolean('cash_only')) {
            $cashCharts = Chart::query()
                ->where(function (Builder $query): void {
                    $query->where('isBank', true)
                        ->orWhere('report_group', ChartReportGroupEnum::CASH_AND_CASH_EQUIVALENTS->value);
                })
                ->get(['id', 'code']);
            $cashChartIds = $cashCharts->pluck('id')->filter()->values();
            $cashChartCodes = $cashCharts->pluck('code')->filter()->values();

            $cashDocuments = Ledger::query()
                ->select('document_number')
                ->whereNotNull('document_number')
                ->where(function (Builder $query) use ($cashChartIds, $cashChartCodes): void {
                    $query->whereIn('chart_id', $cashChartIds)
                        ->orWhereIn('chart_code', $cashChartCodes);
                });

            $query->whereIn('document_number', $cashDocuments);
        }

        if (is_string($sortBy) && array_key_exists($sortBy, $allowedSortColumns)) {
            $query->orderBy($allowedSortColumns[$sortBy], $order);
        } else {
            $query->latest('date');
        }

        return $query;
    }

    /**
     * Display a listing of the resource.
     * Display the read-only General Ledger.
     *
     * @param  Request  $request  The HTTP request instance.
     * @return View Returns the rendered Blade view.
     */
    public function index(Request $request): View
    {
        $this->allows(FileResources::LEDGERS);
        /** @var string|null $search */
        $search = $request->input('search');
        /** @var string|null $startDate */
        $startDate = $request->input('start_date');
        /** @var string|null $endDate */
        $endDate = $request->input('end_date');
        $account = $request->string('account')->trim()->toString();

        $filteredQuery = $this->buildFilteredQuery($request);

        $totals = (clone $filteredQuery)
            ->reorder()
            ->selectRaw('COALESCE(SUM(debit_amount), 0) as total_debit, COALESCE(SUM(credit_amount), 0) as total_credit')
            ->first();
        $totalDebit = (string) ($totals->total_debit ?? '0.00');
        $totalCredit = (string) ($totals->total_credit ?? '0.00');
        $totalBalance = bcsub($totalDebit, $totalCredit, 2);

        $ledgers = $filteredQuery
            ->paginate(self::PER_PAGE_GRID_STANDARD)
            ->withQueryString();
        $sourceUrls = $this->sourceUrls($ledgers->getCollection());

        return view('finance.ledgers.index', compact(
            'ledgers',
            'search',
            'startDate',
            'endDate',
            'account',
            'sourceUrls',
            'totalDebit',
            'totalCredit',
            'totalBalance'
        ));
    }
}
