<?php

/**
 * ============================================================================
 *
 * @author      redfluz
 *
 * @github      https://github.com/redfluz
 *
 * @youtube     https://www.youtube.com/@redfluz
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

namespace App\Http\Controllers\Bank\Refund;

use App\Enums\FileResources;
use App\Enums\LedgerStatus;
use App\Http\Controllers\Controller;
use App\Models\Bank\Refund\CustomerRefund;
use App\Models\System\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Refund history: the refunds that have been posted.
 *
 * Posted refunds are history. They are never edited and never deleted, only
 * looked at and printed, so nothing on this controller changes a document. The
 * documents themselves write the ledgers when they are approved; this only
 * reads them back.
 *
 * Every refund shown here carries who prepared it and who approved it, and when,
 * because a posted refund is an accounting record and the question it usually
 * gets asked is who put it there.
 */
class CustomerHistoryController extends Controller
{
    /**
     * The permission resource for this controller.
     */
    private function resource(): FileResources
    {
        return FileResources::REFUNDS_HISTORY;
    }

    /**
     * Display a listing of posted refunds.
     *
     * @param  Request  $request  The incoming request containing list filters.
     * @return View Returns the rendered Blade view.
     */
    public function index(Request $request): View
    {
        $this->allows($this->resource());

        /** @var string $search */
        $search = $request->input('search', '');
        $search = trim($search);
        /** @var string $startDate */
        $startDate = $request->input('start_date') ?? '';
        /** @var string $endDate */
        $endDate = $request->input('end_date') ?? '';

        $refunds = $this->buildQuery($search, $startDate, $endDate)
            ->paginate(self::PER_PAGE_GRID_NORMAL)
            ->withQueryString();

        return view('bank.customer-refund.history.index', compact('refunds', 'search', 'startDate', 'endDate'));
    }

    /**
     * Display the specified posted refund.
     *
     * @param  string  $documentNumber  The document number of the refund.
     * @return View Returns the rendered Blade view.
     */
    public function show(string $documentNumber): View
    {
        $this->allows($this->resource());

        $refund = CustomerRefund::query()
            ->where('document_number', $documentNumber)
            ->where('status', LedgerStatus::POSTED)
            ->with('lines')
            ->firstOrFail();

        return view('bank.customer-refund.history.show', compact('refund', 'documentNumber'));
    }

    /**
    /**
     * Print one posted refund.
     *
     * @param  string  $documentNumber  The document number of the refund.
     * @return Response Returns the PDF download response.
     */
    public function pdf(string $documentNumber): Response
    {
        $this->allows($this->resource());

        $refund = CustomerRefund::query()
            ->where('document_number', $documentNumber)
            ->where('status', LedgerStatus::POSTED)
            ->with('lines')
            ->firstOrFail();

        return Pdf::loadView('bank.customer-refund.pdf', [
            'refund' => $refund,
            'documentNumber' => $documentNumber,
            'setting' => Setting::query()->first(),
        ])
            ->setPaper('a4')
            ->download("refund_$documentNumber.pdf");
    }

    /**
    /**
     * Print the whole posted listing.
     *
     * @param  Request  $request  The incoming request containing list filters.
     * @return Response Returns the PDF download response.
     */
    public function exportPdf(Request $request): Response
    {
        $this->allows($this->resource());

        /** @var string $search */
        $search = $request->input('search', '');
        $search = trim($search);
        /** @var string $startDate */
        $startDate = $request->input('start_date') ?? '';
        /** @var string $endDate */
        $endDate = $request->input('end_date') ?? '';

        return Pdf::loadView('bank.customer-refund.history.pdf', [
            'refunds' => $this->buildQuery($search, $startDate, $endDate)->get(),
            'startDate' => $startDate,
            'endDate' => $endDate,
            'setting' => Setting::query()->first(),
        ])
            ->setPaper('a4', 'landscape')
            ->download('posted_refunds_'.now()->format('Ymd_His').'.pdf');
    }

    /**
    /**
     * Export the whole posted listing to Excel.
     *
     * @param  Request  $request  The incoming request containing list filters.
     * @return StreamedResponse Returns the Excel download response.
     */
    public function exportExcel(Request $request): StreamedResponse
    {
        $this->allows($this->resource());

        /** @var string $search */
        $search = $request->input('search', '');
        $search = trim($search);
        /** @var string $startDate */
        $startDate = $request->input('start_date') ?? '';
        /** @var string $endDate */
        $endDate = $request->input('end_date') ?? '';

        $refunds = $this->buildQuery($search, $startDate, $endDate)->get();

        $headers = [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="posted_refunds_'.now()->format('Ymd_His').'.xlsx"',
        ];

        return response()->streamDownload(function () use ($refunds): void {
            $handle = fopen('php://output', 'wb');

            if ($handle === false) {
                return;
            }

            fputcsv($handle, [
                __('ledgers.table.column_document_number'),
                __('ledgers.table.column_reference_number'),
                __('ledgers.table.column_date'),
                __('ledgers.table.column_company'),
                __('ledgers.table.column_total_amount'),
                __('refunds.audit.created_by'),
                __('refunds.audit.created_at'),
                __('refunds.audit.approved_by'),
                __('refunds.audit.approved_at'),
            ]);
            /** @var CustomerRefund $refund */
            foreach ($refunds as $refund) {
                fputcsv($handle, [
                    $refund->document_number,
                    $refund->reference_number,
                    $refund->date->format('Y-m-d'),
                    $refund->company_name,
                    number_format((float) $refund->total_amount, 2, '.', ''),
                    $refund->created_name ?? '',
                    $refund->created_at?->format('Y-m-d H:i') ?? '',
                    $refund->approved_name ?? '',
                    $refund->approved_at?->format('Y-m-d H:i') ?? '',
                ]);
            }

            fclose($handle);
        }, 'posted_refunds_'.now()->format('Ymd_His').'.xlsx', $headers);
    }

    /**
     * The posted refunds matching the filters.
     *
     * @param  string  $search  Free text across the number, reference and customer.
     * @param  string|null  $startDate  Earliest document date.
     * @param  string|null  $endDate  Latest document date.
     * @return Builder<CustomerRefund>
     */
    private function buildQuery(string $search, ?string $startDate, ?string $endDate): Builder
    {
        return CustomerRefund::query()
            ->where('status', LedgerStatus::POSTED)
            ->when($search !== '', fn (Builder $query) => $query->where(
                fn (Builder $q) => $q->where('document_number', 'like', "%$search%")
                    ->orWhere('reference_number', 'like', "%$search%")
                    ->orWhere('company_name', 'like', "%$search%")
            ))
            ->when(filled($startDate), fn (Builder $query) => $query->whereDate('date', '>=', $startDate))
            ->when(filled($endDate), fn (Builder $query) => $query->whereDate('date', '<=', $endDate))
            ->orderByDesc('date')
            ->orderByDesc('document_number');
    }
}
