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

namespace App\Http\Controllers\Purchase\ManualAdjustment;

use App\Enums\FileResources;
use App\Enums\Finance\LedgerStatus;
use App\Http\Controllers\Controller;
use App\Models\Finance\ManualPurchaseAdjustment;
use App\Models\System\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Class HistoryController
 *
 * Lists, views, and exports posted manual purchase adjustments. History pages
 * are read-only records of what has been posted. Manual adjustments print from
 * chart accounts rather than product lines, so they keep their own history
 * pages instead of sharing the linked adjustment's.
 */
class HistoryController extends Controller
{
    /**
     * Build a filtered query for posted manual purchase adjustments.
     *
     * @param  Request  $request  The incoming request containing list filters.
     * @return Builder<ManualPurchaseAdjustment> Returns the query builder instance.
     */
    private function buildQuery(Request $request): Builder
    {
        $search = $request->string('search')->toString();
        $startDate = $request->string('start_date')->toString();
        $endDate = $request->string('end_date')->toString();

        return ManualPurchaseAdjustment::query()
            ->with(['company'])
            ->where('status', LedgerStatus::POSTED)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('document_number', 'like', "%$search%")
                        ->orWhere('reference_number', 'like', "%$search%")
                        ->orWhere('company_name', 'like', "%$search%")
                        ->orWhere('notes', 'like', "%$search%")
                        ->orWhereHas('charts', fn ($chartQuery) => $chartQuery->where('description', 'like', "%$search%"));
                });
            })
            ->when($startDate !== '', fn ($query) => $query->whereDate('date', '>=', $startDate))
            ->when($endDate !== '', fn ($query) => $query->whereDate('date', '<=', $endDate))
            ->orderByDesc('date')
            ->orderByDesc('created_at');
    }

    /**
     * Display a listing of posted manual purchase adjustments.
     *
     * @param  Request  $request  The incoming request containing list filters.
     * @return View Returns the rendered Blade view.
     */
    public function index(Request $request): View
    {
        $this->allows(FileResources::MANUAL_PURCHASE_ADJUSTMENTS);

        $search = $request->string('search')->toString();
        $startDate = $request->string('start_date')->toString();
        $endDate = $request->string('end_date')->toString();

        $entries = $this->buildQuery($request)
            ->paginate(self::PER_PAGE_GRID_NORMAL)
            ->withQueryString();

        return view('purchase.manual-adjustments.history.index', compact(
            'entries',
            'search',
            'startDate',
            'endDate',
        ));
    }

    /**
     * Display the specified posted manual purchase adjustment.
     *
     * @param  string  $documentNumber  The document number of the posted manual adjustment.
     * @return View Returns the rendered Blade view.
     */
    public function show(string $documentNumber): View
    {
        $this->allows(FileResources::MANUAL_PURCHASE_ADJUSTMENTS);

        /** @var ManualPurchaseAdjustment|null $adjustment */
        $adjustment = ManualPurchaseAdjustment::query()
            ->with(['company', 'charts'])
            ->where('document_number', $documentNumber)
            ->where('status', LedgerStatus::POSTED)
            ->first();

        abort_if($adjustment === null, 404);

        return view('purchase.manual-adjustments.history.show', [
            'adjustment' => $adjustment,
            'documentNumber' => $documentNumber,
        ]);
    }

    /**
     * Export posted manual purchase adjustments to PDF.
     *
     * @param  Request  $request  The incoming request containing list filters.
     * @return Response Returns the PDF download response.
     */
    public function exportPdf(Request $request): Response
    {
        $this->allows(FileResources::MANUAL_PURCHASE_ADJUSTMENTS);

        $entries = $this->buildQuery($request)->get();

        $setting = Setting::query()->first();

        return Pdf::loadView('purchase.manual-adjustments.history.pdf', compact('entries') + ['setting' => $setting])
            ->setPaper('a4', 'landscape')
            ->download('posted_manual_purchase_adjustments_'.now()->format('Ymd_His').'.pdf');
    }

    /**
     * Export posted manual purchase adjustments to Excel.
     *
     * @param  Request  $request  The incoming request containing list filters.
     * @return StreamedResponse Returns the Excel download response.
     */
    public function exportExcel(Request $request): StreamedResponse
    {
        $this->allows(FileResources::MANUAL_PURCHASE_ADJUSTMENTS);

        $entries = $this->buildQuery($request)->get();
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getProperties()
            ->setCreator(is_string(config('app.name')) ? config('app.name') : '')
            ->setTitle('Posted Manual Purchase Adjustments');

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Posted Manual Purchase Adjustments');

        $headers = [
            __('ledgers.table.column_document_number'),
            __('ledgers.table.column_reference_number'),
            __('manual_purchase_adjustments.form.note_type'),
            __('ledgers.table.column_date'),
            __('ledgers.table.column_company'),
            __('ledgers.table.column_total_amount'),
        ];

        foreach ($headers as $index => $header) {
            $sheet->setCellValue([$index + 1, 1], $header);
        }
        /** @var ManualPurchaseAdjustment $entry */
        foreach ($entries as $index => $entry) {
            $row = $index + 2;
            $sheet->setCellValue([1, $row], $entry->document_number);
            $sheet->setCellValue([2, $row], $entry->reference_number ?? '-');
            $sheet->setCellValue([3, $row], $entry->subtype->label());
            $sheet->setCellValue([4, $row], Carbon::parse($entry->date)->format('d/m/Y'));
            $sheet->setCellValue([5, $row], $entry->company_name ?? $entry->company->name ?? '-');
            $sheet->setCellValue([6, $row], (float) $entry->total_amount);
        }

        $lastRow = (int) max(1, $entries->count() + 1);
        $sheet->getStyle('A1:F1')->getFont()->setBold(true);
        $sheet->getStyle('A1:F1')->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle("F2:F$lastRow")->getNumberFormat()->setFormatCode('#,##0.00;[Red]-#,##0.00');
        $sheet->getStyle("F2:F$lastRow")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:F'.$lastRow);

        foreach (range('A', 'F') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $filename = 'posted_manual_purchase_adjustments_'.now()->format('Ymd_His').'.xlsx';

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
