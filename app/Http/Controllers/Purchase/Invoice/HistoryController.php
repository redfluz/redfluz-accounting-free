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

namespace App\Http\Controllers\Purchase\Invoice;

use App\Enums\FileAction;
use App\Enums\FileResources;
use App\Enums\LedgerStatus;
use App\Http\Controllers\Controller;
use App\Models\Purchase\PurchaseInvoice;
use App\Models\System\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Class HistoryController
 *
 * Lists, views, and exports posted purchase invoices.
 */
class HistoryController extends Controller
{
    /**
     * Build a filtered query for posted purchase invoices.
     *
     * @param  Request  $request  The incoming request containing list filters.
     * @return Builder<PurchaseInvoice> Returns the query builder instance.
     */
    private function buildQuery(Request $request): Builder
    {
        $search = $request->string('search')->value();
        $startDate = $request->string('start_date')->value();
        $endDate = $request->string('end_date')->value();

        return PurchaseInvoice::query()
            ->with(['company'])
            ->where('status', LedgerStatus::POSTED)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('document_number', 'like', "%$search%")
                        ->orWhere('reference_number', 'like', "%$search%")
                        ->orWhere('company_name', 'like', "%$search%")
                        ->orWhereHas('lines.product', fn ($pq) => $pq->where('name', 'like', "%$search%"));
                });
            })
            ->when($startDate !== '', fn ($q) => $q->whereDate('date', '>=', $startDate))
            ->when($endDate !== '', fn ($q) => $q->whereDate('date', '<=', $endDate))
            ->orderByDesc('date');
    }

    /**
     * Display a listing of posted purchase invoices.
     *
     * @param  Request  $request  The incoming request containing list filters.
     * @return View Returns the rendered Blade view.
     */
    public function index(Request $request): View
    {
        $this->allows(FileResources::PURCHASE_INVOICES);

        $search = $request->string('search')->value();
        $startDate = $request->string('start_date')->value();
        $endDate = $request->string('end_date')->value();

        $entries = $this->buildQuery($request)
            ->paginate(self::PER_PAGE_GRID_NORMAL)
            ->withQueryString();

        return view('purchase.invoices.history.index', [
            'entries' => $entries,
            'search' => $search,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'postingDateMin' => null,
            'postingDateMax' => null,
        ]);
    }

    /**
     * Display the specified posted purchase invoice.
     *
     * @param  string  $documentNumber  The document number of the invoice to display.
     * @return View Returns the rendered Blade view.
     */
    public function show(string $documentNumber): View
    {
        $this->allows(FileResources::PURCHASE_INVOICES);

        $invoice = PurchaseInvoice::query()
            ->with(['company', 'contact', 'lines.product'])
            ->where('document_number', $documentNumber)
            ->where('status', LedgerStatus::POSTED)
            ->first();

        if ($invoice === null) {
            abort(404);
        }

        $lines = $invoice->lines;

        return view('purchase.invoices.history.show', array_merge(
            compact('documentNumber', 'lines', 'invoice'),
            ['postingDateMin' => null, 'postingDateMax' => null],
        ));
    }

    /**
     * Clone a posted purchase invoice and redirect to create page with pre-filled data.
     *
     * @param  string  $documentNumber  The document number of the invoice to clone.
     * @return RedirectResponse Redirects to the create page with session data.
     */
    public function clone(string $documentNumber): RedirectResponse
    {
        $this->allows(FileResources::PURCHASE_INVOICES, FileAction::ADD);

        $invoice = PurchaseInvoice::query()
            ->with(['lines'])
            ->where('document_number', $documentNumber)
            ->where('status', LedgerStatus::POSTED)
            ->first();

        if ($invoice === null) {
            abort(404);
        }

        $cloneData = [
            'company_id' => $invoice->company_id,
            'contact_id' => $invoice->contact_id,
            'date' => now()->toDateString(),
            'reference_number' => $invoice->reference_number,
            'lines' => $invoice->lines->map(fn ($line) => [
                'product_id' => $line->product_id,
                'description' => $line->description,
                'quantity' => $line->quantity,
                'unit_price' => $line->unit_price,
                'discount_amount' => $line->discount_amount,
                'tax_amount' => $line->tax_amount,
                'total_amount' => $line->total_amount,
            ])->toArray(),
        ];

        return redirect()->route('purchase.invoices.create')->with('clone_data', $cloneData);
    }

    /**
     * Export posted purchase invoices to PDF.
     *
     * @param  Request  $request  The incoming request containing list filters.
     * @return Response Returns the PDF download response.
     */
    public function exportPdf(Request $request): Response
    {
        $this->allows(FileResources::PURCHASE_INVOICES);

        $entries = $this->buildQuery($request)->get();
        $setting = Setting::query()->first();

        return Pdf::loadView('purchase.invoices.history.pdf', ['setting' => $setting, ...compact('entries')])
            ->setPaper('a4', 'landscape')
            ->download('posted_purchase_invoices_'.now()->format('Ymd_His').'.pdf');
    }

    /**
     * Export posted purchase invoices to Excel.
     *
     * @param  Request  $request  The incoming request containing list filters.
     * @return StreamedResponse Returns the Excel download response.
     */
    public function exportExcel(Request $request): StreamedResponse
    {
        $this->allows(FileResources::PURCHASE_INVOICES);

        $entries = $this->buildQuery($request)->get();
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getProperties()
            ->setCreator(is_string(config('app.name')) ? config('app.name') : 'Redfluz')
            ->setTitle('Posted Purchase Invoices');

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Posted Purchase Invoices');

        $headers = [
            __('ledgers.table.column_document_number'),
            __('ledgers.table.column_reference_number'),
            __('ledgers.table.column_date'),
            __('ledgers.table.column_company'),
            __('ledgers.table.column_total_amount'),
        ];

        foreach ($headers as $index => $header) {
            $sheet->setCellValue([$index + 1, 1], $header);
        }
        /**
         * @var PurchaseInvoice $entry
         */
        foreach ($entries as $index => $entry) {
            $row = $index + 2;
            $sheet->setCellValue([1, $row], $entry->document_number);
            $sheet->setCellValue([2, $row], $entry->reference_number ?? '-');
            $sheet->setCellValue([3, $row], Carbon::parse($entry->date)->format('d/m/Y'));
            $sheet->setCellValue([4, $row], $entry->company_name ?? '-');
            $sheet->setCellValue([5, $row], (float) $entry->total_amount);
        }

        $lastRow = max(1, $entries->count() + 1);
        $sheet->getStyle('A1:E1')->getFont()->setBold(true);
        $sheet->getStyle('A1:E1')->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle("E2:E$lastRow")->getNumberFormat()->setFormatCode('#,##0.00;[Red]-#,##0.00');
        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:E'.$lastRow);

        foreach (range('A', 'E') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $filename = 'posted_purchase_invoices_'.now()->format('Ymd_His').'.xlsx';

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
