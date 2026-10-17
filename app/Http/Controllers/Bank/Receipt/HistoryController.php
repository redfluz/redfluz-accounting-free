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

namespace App\Http\Controllers\Bank\Receipt;

use App\Enums\FileResources;
use App\Enums\LedgerStatus;
use App\Http\Controllers\Controller;
use App\Models\Bank\Receipt;
use App\Models\System\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Receipt history: the receipts that have been posted.
 *
 * Posted receipts are history. They are never edited and never deleted, only
 * looked at and printed, so nothing on this controller changes a document. The
 * documents themselves write the ledgers when they are approved; this only
 * reads them back.
 *
 * Every receipt shown here carries who prepared it and who approved it, and when,
 * because a posted receipt is an accounting record and the question it usually
 * gets asked is who put it there.
 */
class HistoryController extends Controller
{
    /**
     * Display a listing of posted receipts.
     *
     * @param  Request  $request  The incoming request containing list filters.
     * @return View Returns the rendered Blade view.
     */
    public function index(Request $request): View
    {
        $this->allows(FileResources::RECEIPTS_HISTORY);

        /** @var string $search */
        $search = $request->input('search', '');
        $search = trim($search);
        /** @var string $startDate */
        $startDate = $request->input('start_date') ?? '';
        /** @var string $endDate */
        $endDate = $request->input('end_date') ?? '';

        $receipts = $this->buildQuery($search, $startDate, $endDate)
            ->paginate(self::PER_PAGE_GRID_NORMAL)
            ->withQueryString();

        return view('bank.receipt.history.index', compact('receipts', 'search', 'startDate', 'endDate'));
    }

    /**
     * Display the specified posted receipt.
     *
     * @param  string  $documentNumber  The document number of the receipt.
     * @return View Returns the rendered Blade view.
     */
    public function show(string $documentNumber): View
    {
        $this->allows(FileResources::RECEIPTS_HISTORY);

        $receipt = Receipt::query()
            ->where('document_number', $documentNumber)
            ->where('status', LedgerStatus::POSTED)
            ->with('lines')
            ->firstOrFail();

        return view('bank.receipt.history.show', compact('receipt', 'documentNumber'));
    }

    /**
     * Print one posted receipt.
     *
     * @param  string  $documentNumber  The document number of the receipt.
     * @return Response Returns the PDF download response.
     */
    public function pdf(string $documentNumber): Response
    {
        $this->allows(FileResources::RECEIPTS_HISTORY);

        $receipt = Receipt::query()
            ->where('document_number', $documentNumber)
            ->where('status', LedgerStatus::POSTED)
            ->with('lines')
            ->firstOrFail();

        return Pdf::loadView('bank.receipt.pdf', [
            'receipt' => $receipt,
            'documentNumber' => $documentNumber,
            'setting' => Setting::query()->first(),
        ])
            ->setPaper('a4')
            ->download("receipt_$documentNumber.pdf");
    }

    /**
     * Print the whole posted listing.
     *
     * @param  Request  $request  The incoming request containing list filters.
     * @return Response Returns the PDF download response.
     */
    public function exportPdf(Request $request): Response
    {
        $this->allows(FileResources::RECEIPTS_HISTORY);

        /** @var string $search */
        $search = $request->input('search', '');
        $search = trim($search);
        /** @var string $startDate */
        $startDate = $request->input('start_date') ?? '';
        /** @var string $endDate */
        $endDate = $request->input('end_date') ?? '';

        return Pdf::loadView('bank.receipt.history.pdf', [
            'receipts' => $this->buildQuery($search, $startDate, $endDate)->get(),
            'startDate' => $startDate,
            'endDate' => $endDate,
            'setting' => Setting::query()->first(),
        ])
            ->setPaper('a4', 'landscape')
            ->download('posted_receipts_'.now()->format('Ymd_His').'.pdf');
    }

    /**
     * Export the whole posted listing to Excel.
     *
     * @param  Request  $request  The incoming request containing list filters.
     * @return StreamedResponse Returns the Excel download response.
     */
    public function exportExcel(Request $request): StreamedResponse
    {
        $this->allows(FileResources::RECEIPTS_HISTORY);

        /** @var string $search */
        $search = $request->input('search', '');
        $search = trim($search);
        /** @var string $startDate */
        $startDate = $request->input('start_date') ?? '';
        /** @var string $endDate */
        $endDate = $request->input('end_date') ?? '';

        $receipts = $this->buildQuery($search, $startDate, $endDate)->get();

        $headers = [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="posted_receipts_'.now()->format('Ymd_His').'.xlsx"',
        ];

        return response()->streamDownload(function () use ($receipts): void {
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
                __('receipts.audit.created_by'),
                __('receipts.audit.created_at'),
                __('receipts.audit.approved_by'),
                __('receipts.audit.approved_at'),
            ]);
            /** @var Receipt $receipt */
            foreach ($receipts as $receipt) {
                fputcsv($handle, [
                    $receipt->document_number,
                    $receipt->reference_number,
                    $receipt->date->format('Y-m-d'),
                    $receipt->company_name,
                    number_format((float) $receipt->total_amount, 2, '.', ''),
                    $receipt->created_name ?? '',
                    $receipt->created_at?->format('Y-m-d H:i') ?? '',
                    $receipt->approved_name ?? '',
                    $receipt->approved_at?->format('Y-m-d H:i') ?? '',
                ]);
            }

            fclose($handle);
        }, 'posted_receipts_'.now()->format('Ymd_His').'.xlsx', $headers);
    }

    /**
     * The posted receipts matching the filters.
     *
     * @param  string  $search  Free text across the number, reference and customer.
     * @param  string|null  $startDate  Earliest document date.
     * @param  string|null  $endDate  Latest document date.
     * @return Builder<Receipt>
     */
    private function buildQuery(string $search, ?string $startDate, ?string $endDate): Builder
    {
        return Receipt::query()
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
