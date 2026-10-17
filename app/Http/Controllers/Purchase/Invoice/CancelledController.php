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
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Class CancelledController
 *
 * Lists, views, restores, and exports cancelled purchase invoices.
 */
class CancelledController extends Controller
{
    /**
     * Build a filtered query for cancelled purchase invoices.
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
            ->where('status', LedgerStatus::CANCELLED)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('document_number', 'like', "%$search%")
                        ->orWhere('reference_number', 'like', "%$search%")
                        ->orWhereHas('company', fn ($q) => $q->where('name', 'like', "%$search%"));
                });
            })
            ->when($startDate !== '', fn ($query) => $query->whereDate('date', '>=', $startDate))
            ->when($endDate !== '', fn ($query) => $query->whereDate('date', '<=', $endDate))
            ->orderByDesc('date');
    }

    /**
     * Display a listing of cancelled purchase invoices.
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

        return view('purchase.invoices.cancelled.index', compact(
            'entries',
            'search',
            'startDate',
            'endDate',
        ));
    }

    /**
     * Display the specified cancelled purchase invoice.
     *
     * Redirects to the live invoice when the record has already been restored,
     * because this page is only meaningful while the invoice is cancelled.
     *
     * @param  string  $documentNumber  The document number of the cancelled invoice.
     * @return View|RedirectResponse The rendered page, or a redirect to the live invoice.
     */
    public function show(string $documentNumber): View|RedirectResponse
    {
        $this->allows(FileResources::PURCHASE_INVOICES);

        $invoice = PurchaseInvoice::query()
            ->with(['company', 'contact', 'lines.product'])
            ->where('document_number', $documentNumber)
            ->first();

        abort_if($invoice === null, 404);

        // This page exists to explain a cancelled invoice. Once the record has been
        // restored it no longer belongs here, so send the user to the live invoice
        // rather than showing a Cancelled badge for a document that is not.
        if (! $this->isCancelled($documentNumber)) {
            return redirect()
                ->route('purchase.invoices.show', $documentNumber)
                ->with('info', __('common.messages.no_longer_cancelled'));
        }

        $lines = $invoice->lines;

        return view('purchase.invoices.cancelled.show', compact('documentNumber', 'lines'));
    }

    /**
     * Determine whether a document is still in the cancelled state.
     *
     * @param  string  $documentNumber  The document number.
     * @return bool True when the invoice is cancelled.
     */
    private function isCancelled(string $documentNumber): bool
    {
        return PurchaseInvoice::query()
            ->where('document_number', $documentNumber)
            ->where('status', LedgerStatus::CANCELLED)
            ->exists();
    }

    /**
     * Restore a cancelled purchase invoice to pending status.
     *
     * @param  string  $documentNumber  The document number of the cancelled invoice.
     * @return RedirectResponse Redirects to the cancelled invoices index.
     */
    public function uncancel(string $documentNumber): RedirectResponse
    {
        $this->allows(FileResources::PURCHASE_INVOICES, FileAction::APPROVE);

        $restored = PurchaseInvoice::query()
            ->where('document_number', $documentNumber)
            ->where('status', LedgerStatus::CANCELLED)
            ->update(['status' => LedgerStatus::PENDING]);

        // A repeat click matches nothing, because the rows are already pending.
        // Report that and redirect, rather than claiming a restore that never
        // happened and leaving the button on screen to be pressed again.
        if ($restored === 0) {
            return redirect()
                ->route('purchase.invoices.cancelled.index')
                ->with('error', __('common.messages.nothing_to_uncancel'));
        }

        return redirect()
            ->route('purchase.invoices.cancelled.index')
            ->with('success', __('common.messages.uncancelled'));
    }

    /**
     * Export cancelled purchase invoices to PDF.
     *
     * @param  Request  $request  The incoming request containing list filters.
     * @return Response Returns the PDF download response.
     */
    public function exportPdf(Request $request): Response
    {
        $this->allows(FileResources::PURCHASE_INVOICES);

        $entries = $this->buildQuery($request)->get();
        $startDate = $request->string('start_date')->value();
        $endDate = $request->string('end_date')->value();
        $setting = Setting::query()->first();

        return Pdf::loadView('purchase.invoices.cancelled.pdf', ['setting' => $setting, ...compact('entries', 'startDate', 'endDate')])
            ->setPaper('a4', 'landscape')
            ->download('cancelled_purchase_invoices_'.now()->format('Ymd_His').'.pdf');
    }

    /**
     * Export cancelled purchase invoices to Excel.
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
            ->setTitle('Cancelled Purchase Invoices');

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Cancelled Purchase Invoices');

        $headers = [
            __('ledgers.table.column_document_number'),
            __('ledgers.table.column_date'),
            __('ledgers.table.column_subtype'),
            __('ledgers.table.column_company'),
            __('ledgers.table.column_total_amount'),
        ];

        foreach ($headers as $index => $header) {
            $sheet->setCellValue([$index + 1, 1], $header);
        }
        /** @var PurchaseInvoice $entry */
        foreach ($entries as $index => $entry) {
            $row = $index + 2;
            $sheet->setCellValue([1, $row], $entry->document_number);
            $sheet->setCellValue([2, $row], Carbon::parse($entry->date)->format('d M Y'));
            $sheet->setCellValue([3, $row], __('ledgers.sub_type.purchase_invoice'));
            $sheet->setCellValue([4, $row], $entry->company->name ?? $entry->company_name ?? '-');
            $sheet->setCellValue([5, $row], (float) $entry->total_amount);
        }

        $totalRow = $entries->count() + 2;
        $sheet->setCellValue([4, $totalRow], __('common.labels.total'));
        $totalAmount = $entries->sum('total_amount');
        $sheet->setCellValue([5, $totalRow], is_numeric($totalAmount) ? (float) $totalAmount : 0.0);
        $sheet->getStyle('A1:E1')->getFont()->setBold(true);
        $sheet->getStyle('A1:E1')->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle('D'.$totalRow.':E'.$totalRow)->getFont()->setBold(true);
        $sheet->getStyle('D'.$totalRow.':E'.$totalRow)->getBorders()->getTop()->setBorderStyle(Border::BORDER_DOUBLE);
        $sheet->getStyle('E2:E'.$totalRow)->getNumberFormat()->setFormatCode('#,##0.00;[Red]-#,##0.00');
        $sheet->getStyle('E2:E'.$totalRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:E'.max(1, $totalRow - 1));

        foreach (range('A', 'E') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $filename = 'cancelled_purchase_invoices_'.now()->format('Ymd_His').'.xlsx';

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
