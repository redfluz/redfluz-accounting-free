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

namespace App\Http\Controllers\Sales\SalesAdjustment;

use App\Enums\FileResources;
use App\Enums\LedgerStatus;
use App\Http\Controllers\Controller;
use App\Models\Sales\SalesAdjustment;
use App\Models\System\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Class HistoryController
 *
 * Lists, views, and exports posted sales adjustments.
 */
class HistoryController extends Controller
{
    /**
     * Build a filtered query for posted sales adjustments.
     *
     * @param  Request  $request  The incoming request containing list filters.
     * @return Builder<SalesAdjustment> Returns the query builder instance.
     */
    private function buildQuery(Request $request): Builder
    {
        $search = $request->string('search')->toString();
        $startDate = $request->string('start_date')->toString();
        $endDate = $request->string('end_date')->toString();

        return SalesAdjustment::query()
            ->where('status', LedgerStatus::POSTED)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('document_number', 'like', "%$search%")
                        ->orWhere('reference_number', 'like', "%$search%")
                        ->orWhere('company_name', 'like', "%$search%")
                        ->orWhere('notes', 'like', "%$search%");
                });
            })
            ->when($startDate, fn ($q) => $q->whereDate('date', '>=', $startDate))
            ->when($endDate, fn ($q) => $q->whereDate('date', '<=', $endDate))
            ->orderByDesc('date');
    }

    /**
     * Display a listing of posted sales adjustments.
     *
     * @param  Request  $request  The incoming request containing list filters.
     * @return View Returns the rendered Blade view.
     */
    public function index(Request $request): View
    {
        $this->allows(FileResources::SALES_ADJUSTMENTS);

        $search = $request->string('search')->toString();
        $startDate = $request->string('start_date')->toString();
        $endDate = $request->string('end_date')->toString();

        $entries = $this->buildQuery($request)
            ->paginate(self::PER_PAGE_GRID_NORMAL)
            ->withQueryString();

        return view('sales.adjustments.history.index', [
            'entries' => $entries,
            'search' => $search,
            'startDate' => $startDate,
            'endDate' => $endDate,
            ...['postingDateMin' => null, 'postingDateMax' => null],
        ]);
    }

    /**
     * Display the specified posted sales adjustment.
     *
     * @param  string  $documentNumber  The adjustment number of the adjustment to display.
     * @return View Returns the rendered Blade view.
     */
    public function show(string $documentNumber): View
    {
        $this->allows(FileResources::SALES_ADJUSTMENTS);

        $adjustment = SalesAdjustment::query()
            ->where('document_number', $documentNumber)
            ->where('status', LedgerStatus::POSTED)
            ->first();

        if ($adjustment === null) {
            abort(404);
        }

        $lines = $adjustment->lines;

        return view('sales.adjustments.history.show', array_merge(
            compact('documentNumber', 'adjustment', 'lines'),
            ['postingDateMin' => null, 'postingDateMax' => null],
        ));
    }

    /**
     * Export posted sales adjustments to PDF.
     *
     * @param  Request  $request  The incoming request containing list filters.
     * @return Response Returns the PDF download response.
     */
    public function exportPdf(Request $request): Response
    {
        $this->allows(FileResources::SALES_ADJUSTMENTS);

        $entries = $this->buildQuery($request)->get();

        $setting = Setting::query()->first();

        return Pdf::loadView('sales.adjustments.history.pdf', compact('entries') + ['setting' => $setting])
            ->setPaper('a4', 'landscape')
            ->download('posted_sales_adjustments_'.now()->format('Ymd_His').'.pdf');
    }

    /**
     * Export posted sales adjustments to Excel.
     *
     * @param  Request  $request  The incoming request containing list filters.
     * @return StreamedResponse Returns the Excel download response.
     */
    public function exportExcel(Request $request): StreamedResponse
    {
        $this->allows(FileResources::SALES_ADJUSTMENTS);

        $entries = $this->buildQuery($request)->get();
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getProperties()
            ->setCreator(is_string(config('app.name')) ? config('app.name') : '')
            ->setTitle('Posted Sales Adjustments');

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Posted Sales Adjustments');

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
        /**
         * @var SalesAdjustment $entry
         */
        foreach ($entries as $index => $entry) {
            $row = $index + 2;
            $sheet->setCellValue([1, $row], $entry->document_number);
            $sheet->setCellValue([2, $row], $entry->reference_number ?? '-');
            $sheet->setCellValue([3, $row], Carbon::parse($entry->date)->format('d/m/Y'));
            $sheet->setCellValue([4, $row], __($entry->subtype->value));
            $sheet->setCellValue([5, $row], $entry->company_name ?? '-');
            $sheet->setCellValue([6, $row], (float) $entry->total_amount);
        }

        $lastRow = (int) max(1, $entries->count() + 1);
        $sheet->getStyle('A1:F1')->getFont()->setBold(true);
        $sheet->getStyle('A1:F1')->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle("F2:F$lastRow")->getNumberFormat()->setFormatCode('#,##0.00;[Red]-#,##0.00');
        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:F'.$lastRow);

        foreach (range('A', 'F') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $filename = 'posted_sales_adjustments_'.now()->format('Ymd_His').'.xlsx';

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
