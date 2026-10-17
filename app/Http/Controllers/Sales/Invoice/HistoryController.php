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

namespace App\Http\Controllers\Sales\Invoice;

use App\Enums\FileAction;
use App\Enums\FileResources;
use App\Enums\LedgerStatus;
use App\Enums\ProductMovementDirection;
use App\Enums\ProductMovementSource;
use App\Enums\ProductMovementStatus;
use App\Enums\SalesType;
use App\Http\Controllers\Controller;
use App\Models\Inventory\ProductLedger;
use App\Models\Sales\SalesInvoice;
use App\Models\Sales\SalesInvoiceLine;
use App\Models\System\Setting;
use App\Services\DecimalService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
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
 * Lists, views, and exports posted sales invoices.
 */
class HistoryController extends Controller
{
    /**
     * Build a filtered query for posted sales invoices.
     *
     * @param  Request  $request  The incoming request containing list filters.
     * @return Builder<SalesInvoice> Returns the query builder instance.
     */
    private function buildQuery(Request $request): Builder
    {
        $search = $request->string('search')->toString();
        $startDate = $request->string('start_date')->toString();
        $endDate = $request->string('end_date')->toString();

        return SalesInvoice::query()
            ->with(['company', 'contact'])
            ->where('status', LedgerStatus::POSTED)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('document_number', 'like', "%$search%")
                        ->orWhere('reference_number', 'like', "%$search%")
                        ->orWhere('company_name', 'like', "%$search%")
                        ->orWhereHas('lines.product', fn ($pq) => $pq->where('name', 'like', "%$search%"));
                });
            })
            ->when($startDate, fn ($q) => $q->whereDate('date', '>=', $startDate))
            ->when($endDate, fn ($q) => $q->whereDate('date', '<=', $endDate))
            ->orderByDesc('date');
    }

    /**
     * Display a listing of posted sales invoices.
     *
     * @param  Request  $request  The incoming request containing list filters.
     * @return View Returns the rendered Blade view.
     */
    public function index(Request $request): View
    {
        $this->allows(FileResources::SALES_INVOICES);

        $search = $request->string('search')->toString();
        $startDate = $request->string('start_date')->toString();
        $endDate = $request->string('end_date')->toString();

        $entries = $this->buildQuery($request)
            ->paginate(self::PER_PAGE_GRID_NORMAL)
            ->withQueryString();

        return view('sales.invoices.history.index', [
            'entries' => $entries,
            'search' => $search,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
    }

    /**
     * Display the specified posted sales invoice.
     *
     * @param  string  $documentNumber  The document number of the invoice to display.
     * @return View Returns the rendered Blade view.
     */
    public function show(string $documentNumber): View
    {
        $this->allows(FileResources::SALES_INVOICES);

        /** @var SalesInvoice|null $invoice */
        $invoice = SalesInvoice::query()
            ->with(['company', 'contact', 'lines.product'])
            ->where('document_number', $documentNumber)
            ->where('status', LedgerStatus::POSTED)
            ->first();

        if ($invoice === null) {
            abort(404);
        }

        /** @var Collection<int, SalesInvoiceLine> $lines */
        $lines = $invoice->lines;

        $canDeductInventory = $lines->isNotEmpty()
            && $invoice->sales_type === SalesType::PRODUCT
            && $lines->every(fn (SalesInvoiceLine $line): bool => ! $line->deduct_inventory)
            && ! ProductLedger::query()
                ->where('document_number', $documentNumber)
                ->where('status', ProductMovementStatus::ACTIVE->value)
                ->exists();

        // Per-line return audit: what was invoiced, how much already came
        // back, and what is still returnable for every product line.
        /** @var array<string, array{id: string, product_name: string|null, quantity: string, returned: string, remaining: numeric-string, unit_price: string}> $lineReturns */
        $lineReturns = [];

        foreach ($lines as $line) {
            /** @var SalesInvoiceLine $line */
            if ($line->product_id === null) {
                continue;
            }

            $returnedIn = (string) ProductLedger::query()
                ->whereIn('source_type', [ProductMovementSource::SALES_INVOICE, ProductMovementSource::SALES_RETURN])
                ->where('direction', ProductMovementDirection::IN)
                ->whereNull('deleted_at')
                ->where('source_entry_id', $line->id)
                ->where('status', ProductMovementStatus::ACTIVE->value)
                ->sum('quantity');

            $quantity = DecimalService::normalize($line->quantity !== null ? (string) $line->quantity : '0');
            $returned = DecimalService::normalize($returnedIn);

            $lineReturns[$line->id] = [
                'id' => $line->id,
                'product_name' => $line->product_name,
                'quantity' => $quantity,
                'returned' => $returned,
                'remaining' => bcsub($quantity, $returned, 4),
                'unit_price' => DecimalService::normalize((string) ($line->unit_price ?? 0)),
            ];
        }

        /** @var list<array{id: string, product_name: string|null, quantity: string, returned: string, remaining: numeric-string, unit_price: string}> $returnableItems */
        $returnableItems = [];

        foreach ($lineReturns as $lineReturn) {
            if (bccomp($lineReturn['remaining'], '0.0000', 4) === 1) {
                $returnableItems[] = $lineReturn;
            }
        }

        /** @var \Illuminate\Support\Collection<int, array{id: string, product_name: string|null, quantity: string, returned: string, remaining: numeric-string, unit_price: string}> $returnableLines */
        $returnableLines = new \Illuminate\Support\Collection($returnableItems);

        return view('sales.invoices.history.show', compact('documentNumber', 'lines', 'invoice', 'canDeductInventory', 'lineReturns', 'returnableLines'));
    }

    /**
     * Clone a posted sales invoice and redirect to create page with pre-filled data.
     *
     * @param  string  $documentNumber  The document number of the invoice to clone.
     * @return RedirectResponse Redirects to the create page with session data.
     */
    public function clone(string $documentNumber): RedirectResponse
    {
        $this->allows(FileResources::SALES_INVOICES, FileAction::ADD);

        /** @var SalesInvoice|null $invoice */
        $invoice = SalesInvoice::query()
            ->with(['lines'])
            ->where('document_number', $documentNumber)
            ->where('status', LedgerStatus::POSTED)
            ->first();

        if ($invoice === null) {
            abort(404);
        }

        /** @var Collection<int, SalesInvoiceLine> $lines */
        $lines = $invoice->lines;

        /** @var SalesInvoiceLine|null $firstLine */
        $firstLine = $lines->first();

        $cloneData = [
            'company_id' => $invoice->company_id,
            'contact_id' => $invoice->contact_id,
            'sales_type' => $invoice->sales_type->value,
            'date' => now()->toDateString(),
            'reference_number' => $invoice->reference_number,
            'lines' => $lines->map(fn (SalesInvoiceLine $line): array => [
                'product_id' => $line->product_id,
                'description' => $line->description,
                'quantity' => $line->quantity,
                'unit_price' => $line->unit_price,
                'discount_id' => $line->discount_id,
                'discount_type' => $line->discount_type,
                'discount_value' => $line->discount_value,
                'discount_amount' => $line->discount_amount,
                'tax_id' => $line->tax_id,
                'tax_rate' => $line->tax_rate,
                'tax_mode' => $line->tax_mode,
                'tax_amount' => $line->tax_amount,
                'total_amount' => $line->total_amount,
            ])->toArray(),
        ];

        return redirect()->route('sales.invoices.create')->with('clone_data', $cloneData);
    }

    /**
     * Export posted sales invoices to PDF.
     *
     * @param  Request  $request  The incoming request containing list filters.
     * @return Response Returns the PDF download response.
     */
    public function exportPdf(Request $request): Response
    {
        $this->allows(FileResources::SALES_INVOICES);

        $entries = $this->buildQuery($request)->get();

        $setting = Setting::query()->first();

        return Pdf::loadView('sales.invoices.history.pdf', compact('entries') + ['setting' => $setting])
            ->setPaper('a4', 'landscape')
            ->download('posted_sales_invoices_'.now()->format('Ymd_His').'.pdf');
    }

    /**
     * Export posted sales invoices to Excel.
     *
     * @param  Request  $request  The incoming request containing list filters.
     * @return StreamedResponse Returns the Excel download response.
     */
    public function exportExcel(Request $request): StreamedResponse
    {
        $this->allows(FileResources::SALES_INVOICES);

        $entries = $this->buildQuery($request)->get();
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getProperties()
            ->setCreator(is_string(config('app.name')) ? config('app.name') : '')
            ->setTitle('Posted Sales Invoices');

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Posted Sales Invoices');

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
         * @var SalesInvoice $entry
         */
        foreach ($entries as $index => $entry) {
            $row = $index + 2;
            $sheet->setCellValue([1, $row], $entry->document_number);
            $sheet->setCellValue([2, $row], $entry->reference_number ?? '-');
            $sheet->setCellValue([3, $row], Carbon::parse($entry->date)->format('d/m/Y'));
            $sheet->setCellValue([4, $row], $entry->company_name ?? '-');
            $sheet->setCellValue([5, $row], (float) $entry->total_amount);
        }

        $lastRow = (int) max(1, $entries->count() + 1);
        $sheet->getStyle('A1:E1')->getFont()->setBold(true);
        $sheet->getStyle('A1:E1')->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle("E2:E$lastRow")->getNumberFormat()->setFormatCode('#,##0.00;[Red]-#,##0.00');
        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:E'.$lastRow);

        foreach (range('A', 'E') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $filename = 'posted_sales_invoices_'.now()->format('Ymd_His').'.xlsx';

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
