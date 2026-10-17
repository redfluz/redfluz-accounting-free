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

namespace App\Http\Controllers\Purchase\PurchaseAdjustment;

use App\Enums\FileAction;
use App\Enums\FileResources;
use App\Enums\LedgerStatus;
use App\Http\Controllers\Controller;
use App\Models\Purchase\PurchaseAdjustment;
use App\Models\System\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Class CancelledController
 *
 * Lists, views, restores, and exports cancelled purchase adjustments.
 */
class CancelledController extends Controller
{
    /**
     * Build a filtered query for cancelled purchase adjustments.
     *
     * @param  Request  $request  The incoming request containing list filters.
     * @return Builder<PurchaseAdjustment> Returns the query builder instance.
     */
    private function buildQuery(Request $request): Builder
    {
        $search = $request->string('search')->value();
        $startDate = $request->string('start_date')->value();
        $endDate = $request->string('end_date')->value();

        // A note is one row in the notes table, so there is nothing to group by.
        return PurchaseAdjustment::query()
            ->where('status', LedgerStatus::CANCELLED)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('document_number', 'like', "%$search%")
                        ->orWhere('reference_number', 'like', "%$search%")
                        ->orWhere('company_name', 'like', "%$search%")
                        ->orWhere('notes', 'like', "%$search%");
                });
            })
            ->when($startDate !== '', fn ($q) => $q->whereDate('date', '>=', $startDate))
            ->when($endDate !== '', fn ($q) => $q->whereDate('date', '<=', $endDate))
            ->orderByDesc('date');
    }

    /**
     * Display a listing of cancelled purchase adjustments.
     *
     * @param  Request  $request  The incoming request containing list filters.
     * @return View Returns the rendered Blade view.
     */
    public function index(Request $request): View
    {
        $this->allows(FileResources::PURCHASE_ADJUSTMENTS);

        $search = $request->string('search')->value();
        $startDate = $request->string('start_date')->value();
        $endDate = $request->string('end_date')->value();

        $entries = $this->buildQuery($request)
            ->paginate(self::PER_PAGE_GRID_NORMAL)
            ->withQueryString();

        return view('purchase.adjustments.cancelled.index', compact(
            'entries',
            'search',
            'startDate',
            'endDate',
        ));
    }

    /**
     * Display the specified cancelled purchase adjustment.
     *
     * @param  string  $documentNumber  The document number of the cancelled adjustment.
     * @return View|RedirectResponse The rendered page, or a redirect to the live record.
     */
    public function show(string $documentNumber): View|RedirectResponse
    {
        $this->allows(FileResources::PURCHASE_ADJUSTMENTS);

        $adjustment = PurchaseAdjustment::query()
            ->with(['company', 'lines'])
            ->where('document_number', $documentNumber)
            ->firstOrFail();

        $entries = $adjustment->lines;

        // This page explains a cancelled document. Once restored it no longer
        // belongs here, and its header badge is hardcoded to say Cancelled.
        $stillCancelled = $adjustment->status === LedgerStatus::CANCELLED;

        if (! $stillCancelled) {
            return redirect()
                ->route('purchase.adjustments.show', $documentNumber)
                ->with('info', __('common.messages.no_longer_cancelled'));
        }

        return view('purchase.adjustments.cancelled.show', compact('documentNumber', 'entries', 'adjustment'));
    }

    /**
     * Restore a cancelled purchase adjustment to pending status.
     *
     * @param  string  $documentNumber  The document number of the cancelled adjustment.
     * @return RedirectResponse Redirects to the cancelled adjustments index.
     */
    public function uncancel(string $documentNumber): RedirectResponse
    {
        $this->allows(FileResources::PURCHASE_ADJUSTMENTS, FileAction::APPROVE);

        $restored = PurchaseAdjustment::query()
            ->where('document_number', $documentNumber)
            ->where('status', LedgerStatus::CANCELLED)
            ->update(['status' => LedgerStatus::PENDING]);

        // A repeat click matches nothing, because the rows are already pending.
        // Report that and redirect, rather than claiming a restore that never
        // happened and leaving the button on screen to be pressed again.
        if ($restored === 0) {
            return redirect()
                ->route('purchase.adjustments.cancelled.index')
                ->with('error', __('common.messages.nothing_to_uncancel'));
        }

        return redirect()
            ->route('purchase.adjustments.cancelled.index')
            ->with('success', __('common.messages.uncancelled'));
    }

    /**
     * Export cancelled purchase adjustments to PDF.
     *
     * @param  Request  $request  The incoming request containing list filters.
     * @return Response Returns the PDF download response.
     */
    public function exportPdf(Request $request): Response
    {
        $this->allows(FileResources::PURCHASE_ADJUSTMENTS);

        $entries = $this->buildQuery($request)->get();
        $startDate = $request->string('start_date')->value();
        $endDate = $request->string('end_date')->value();
        $setting = Setting::query()->first();

        return Pdf::loadView('purchase.adjustments.cancelled.pdf', ['setting' => $setting, ...compact('entries', 'startDate', 'endDate')])
            ->setPaper('a4', 'landscape')
            ->download('cancelled_purchase_adjustments_'.now()->format('Ymd_His').'.pdf');
    }

    /**
     * Export cancelled purchase adjustments to Excel.
     *
     * @param  Request  $request  The incoming request containing list filters.
     * @return StreamedResponse Returns the Excel download response.
     */
    public function exportExcel(Request $request): StreamedResponse
    {
        $this->allows(FileResources::PURCHASE_ADJUSTMENTS);

        $entries = $this->buildQuery($request)->get();
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getProperties()
            ->setCreator(is_string(config('app.name')) ? config('app.name') : 'Redfluz')
            ->setTitle('Cancelled Purchase Adjustments');

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Cancelled Purchase Adjustments');

        $headers = [
            __('ledgers.table.column_document_number'),
            __('ledgers.table.column_reference_number'),
            __('ledgers.table.column_date'),
            __('ledgers.table.column_subtype'),
            __('ledgers.table.column_company'),
            __('ledgers.table.column_total_amount'),
        ];

        foreach ($headers as $index => $header) {
            $sheet->setCellValue([$index + 1, 1], $header);
        }
        foreach ($entries as $index => $entry) {
            $row = $index + 2;
            $sheet->setCellValue([1, $row], $entry->document_number);
            $sheet->setCellValue([2, $row], $entry->reference_number ?? '-');
            $sheet->setCellValue([3, $row], Carbon::parse($entry->date)->format('d M Y'));
            $sheet->setCellValue([4, $row], __('ledgers.sub_type.'.$entry->subtype->value));
            $sheet->setCellValue([5, $row], $entry->company_name ?? '-');
            $sheet->setCellValue([6, $row], (float) $entry->total_amount);
        }

        $totalRow = $entries->count() + 2;
        $sheet->setCellValue([5, $totalRow], __('common.labels.total'));
        $totalAmount = $entries->sum('total_amount');
        $sheet->setCellValue([6, $totalRow], is_numeric($totalAmount) ? (float) $totalAmount : 0.0);
        $sheet->getStyle('A1:F1')->getFont()->setBold(true);
        $sheet->getStyle('A1:F1')->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle('E'.$totalRow.':F'.$totalRow)->getFont()->setBold(true);
        $sheet->getStyle('E'.$totalRow.':F'.$totalRow)->getBorders()->getTop()->setBorderStyle(Border::BORDER_DOUBLE);
        $sheet->getStyle('F2:F'.$totalRow)->getNumberFormat()->setFormatCode('#,##0.00;[Red]-#,##0.00');
        $sheet->getStyle('F2:F'.$totalRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:F'.max(1, $totalRow - 1));

        foreach (range('A', 'F') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $filename = 'cancelled_purchase_adjustments_'.now()->format('Ymd_His').'.xlsx';

        return response()->streamDownload(
            function () use ($spreadsheet): void {
                new Xlsx($spreadsheet)->save('php://output');
                $spreadsheet->disconnectWorksheets();
            },
            $filename,
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );
    }
}
