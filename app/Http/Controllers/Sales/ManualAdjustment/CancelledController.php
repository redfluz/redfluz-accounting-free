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

namespace App\Http\Controllers\Sales\ManualAdjustment;

use App\Enums\FileAction;
use App\Enums\FileResources;
use App\Enums\Finance\LedgerStatus;
use App\Http\Controllers\Controller;
use App\Models\Finance\ManualSalesAdjustment;
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
 * Lists, views, restores, and exports cancelled manual sales adjustments.
 * Manual adjustments print from chart accounts rather than product lines, so
 * they keep their own cancelled pages instead of sharing the linked
 * adjustment's.
 */
class CancelledController extends Controller
{
    /**
     * Build a filtered query for cancelled manual sales adjustments.
     *
     * @param  Request  $request  The incoming request containing list filters.
     * @return Builder<ManualSalesAdjustment> Returns the query builder instance.
     */
    private function buildQuery(Request $request): Builder
    {
        $search = $request->string('search')->toString();
        $startDate = $request->string('start_date')->toString();
        $endDate = $request->string('end_date')->toString();

        return ManualSalesAdjustment::query()
            ->with(['company'])
            ->where('status', LedgerStatus::CANCELLED)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('document_number', 'like', "%$search%")
                        ->orWhere('reference_number', 'like', "%$search%")
                        ->orWhere('company_name', 'like', "%$search%")
                        ->orWhere('notes', 'like', "%$search%");
                });
            })
            ->when($startDate !== '', fn ($query) => $query->whereDate('date', '>=', $startDate))
            ->when($endDate !== '', fn ($query) => $query->whereDate('date', '<=', $endDate))
            ->orderByDesc('date')
            ->orderByDesc('created_at');
    }

    /**
     * Display a listing of cancelled manual sales adjustments.
     *
     * @param  Request  $request  The incoming request containing list filters.
     * @return View Returns the rendered Blade view.
     */
    public function index(Request $request): View
    {
        $this->allows(FileResources::MANUAL_SALES_ADJUSTMENTS);

        $search = $request->string('search')->toString();
        $startDate = $request->string('start_date')->toString();
        $endDate = $request->string('end_date')->toString();

        $entries = $this->buildQuery($request)
            ->paginate(self::PER_PAGE_GRID_NORMAL)
            ->withQueryString();

        return view('sales.manual-adjustments.cancelled.index', compact(
            'entries',
            'search',
            'startDate',
            'endDate',
        ));
    }

    /**
     * Display the specified cancelled manual sales adjustment.
     *
     * @param  string  $documentNumber  The document number of the cancelled manual adjustment.
     * @return View|RedirectResponse The rendered page, or a redirect to the live record.
     */
    public function show(string $documentNumber): View|RedirectResponse
    {
        $this->allows(FileResources::MANUAL_SALES_ADJUSTMENTS);

        /** @var ManualSalesAdjustment|null $adjustment */
        $adjustment = ManualSalesAdjustment::query()
            ->with(['company', 'charts'])
            ->where('document_number', $documentNumber)
            ->first();

        abort_if($adjustment === null, 404);

        // This page explains a cancelled document. Once restored it no longer
        // belongs here, and its header badge is hardcoded to say Cancelled.
        if ($adjustment->status !== LedgerStatus::CANCELLED) {
            return redirect()
                ->route('sales.manual-adjustments.show', $documentNumber)
                ->with('info', __('common.messages.no_longer_cancelled'));
        }

        return view('sales.manual-adjustments.cancelled.show', [
            'adjustment' => $adjustment,
            'documentNumber' => $documentNumber,
        ]);
    }

    /**
     * Print one cancelled manual sales adjustment.
     *
     * @param  string  $documentNumber  The document number of the cancelled manual adjustment.
     * @return Response Returns the PDF download response.
     */
    public function pdf(string $documentNumber): Response
    {
        $this->allows(FileResources::MANUAL_SALES_ADJUSTMENTS);

        /** @var ManualSalesAdjustment|null $adjustment */
        $adjustment = ManualSalesAdjustment::query()
            ->with(['company', 'charts'])
            ->where('document_number', $documentNumber)
            ->where('status', LedgerStatus::CANCELLED)
            ->first();

        abort_if($adjustment === null, 404);

        return Pdf::loadView('sales.manual-adjustments.pdf', [
            'adjustment' => $adjustment,
            'documentNumber' => $documentNumber,
            'setting' => Setting::query()->first(),
        ])
            ->setPaper('a4')
            ->download("manual_sales_adjustment_$documentNumber.pdf");
    }

    /**
     * Restore a cancelled manual sales adjustment to pending status.
     *
     * @param  string  $documentNumber  The document number of the cancelled manual adjustment.
     * @return RedirectResponse Redirects to the cancelled manual sales adjustments index.
     */
    public function uncancel(string $documentNumber): RedirectResponse
    {
        $this->allows(FileResources::MANUAL_SALES_ADJUSTMENTS, FileAction::APPROVE);

        $restored = ManualSalesAdjustment::query()
            ->where('document_number', $documentNumber)
            ->where('status', LedgerStatus::CANCELLED)
            ->update(['status' => LedgerStatus::PENDING]);

        // A repeat click matches nothing, because the rows are already pending.
        // Report that and redirect, rather than claiming a restore that never
        // happened and leaving the button on screen to be pressed again.
        if ($restored === 0) {
            return redirect()
                ->route('sales.manual-adjustments.cancelled.index')
                ->with('error', __('common.messages.nothing_to_uncancel'));
        }

        return redirect()
            ->route('sales.manual-adjustments.cancelled.index')
            ->with('success', __('common.messages.uncancelled'));
    }

    /**
     * Export cancelled manual sales adjustments to PDF.
     *
     * @param  Request  $request  The incoming request containing list filters.
     * @return Response Returns the PDF download response.
     */
    public function exportPdf(Request $request): Response
    {
        $this->allows(FileResources::MANUAL_SALES_ADJUSTMENTS);

        $entries = $this->buildQuery($request)->get();
        $startDate = $request->string('start_date')->toString();
        $endDate = $request->string('end_date')->toString();

        $setting = Setting::query()->first();

        return Pdf::loadView('sales.manual-adjustments.cancelled.pdf', compact('entries', 'startDate', 'endDate') + ['setting' => $setting])
            ->setPaper('a4', 'landscape')
            ->download('cancelled_manual_sales_adjustments_'.now()->format('Ymd_His').'.pdf');
    }

    /**
     * Export cancelled manual sales adjustments to Excel.
     *
     * @param  Request  $request  The incoming request containing list filters.
     * @return StreamedResponse Returns the Excel download response.
     */
    public function exportExcel(Request $request): StreamedResponse
    {
        $this->allows(FileResources::MANUAL_SALES_ADJUSTMENTS);

        $entries = $this->buildQuery($request)->get();
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getProperties()
            ->setCreator(is_string(config('app.name')) ? config('app.name') : '')
            ->setTitle('Cancelled Manual Sales Adjustments');

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Cancelled Manual Sales Adjustments');

        $headers = [
            __('ledgers.table.column_document_number'),
            __('ledgers.table.column_reference_number'),
            __('manual_sales_adjustments.form.note_type'),
            __('ledgers.table.column_date'),
            __('ledgers.table.column_company'),
            __('ledgers.table.column_total_amount'),
        ];

        foreach ($headers as $index => $header) {
            $sheet->setCellValue([$index + 1, 1], $header);
        }
        /** @var ManualSalesAdjustment $entry */
        foreach ($entries as $index => $entry) {
            $row = $index + 2;
            $sheet->setCellValue([1, $row], $entry->document_number);
            $sheet->setCellValue([2, $row], $entry->reference_number ?? '-');
            $sheet->setCellValue([3, $row], $entry->subtype->label());
            $sheet->setCellValue([4, $row], Carbon::parse($entry->date)->format('d/m/Y'));
            $sheet->setCellValue([5, $row], $entry->company_name ?? $entry->company->name ?? '-');
            $sheet->setCellValue([6, $row], (float) $entry->total_amount);
        }

        $totalRow = $entries->count() + 2;
        $sheet->setCellValue([5, $totalRow], __('common.labels.total'));
        $sumTotal = $entries->sum('total_amount');
        $sheet->setCellValue([6, $totalRow], is_numeric($sumTotal) ? (float) $sumTotal : 0.0);
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

        $filename = 'cancelled_manual_sales_adjustments_'.now()->format('Ymd_His').'.xlsx';

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
