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
use App\Enums\Finance\DocumentStatus;
use App\Enums\Finance\LedgerType;
use App\Enums\Sales\SalesType;
use App\Http\Controllers\Controller;
use App\Models\Crm\ArLedger;
use App\Models\Sales\SalesInvoice;
use App\Models\Sales\SalesInvoiceLine;
use App\Models\System\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Class HistoryController
 *
 * Lists, views, and exports posted sales invoices.
 */
class HistoryController extends Controller
{
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

        $entries = SalesInvoice::query()
            ->with(['company', 'contact'])
            ->where('status', DocumentStatus::POSTED)
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
            ->orderByDesc('date')
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
            ->where('status', DocumentStatus::POSTED)
            ->first();

        if ($invoice === null) {
            abort(404);
        }

        /** @var Collection<int, SalesInvoiceLine> $lines */
        $lines = $invoice->lines;

        $deductableLines = $lines
            ->filter(fn (SalesInvoiceLine $line): bool => $line->product_id !== null && ! $line->deduct_inventory)
            ->values();

        $canDeductInventory = $invoice->sales_type === SalesType::PRODUCT && $deductableLines->isNotEmpty();

        return view('sales.invoices.history.show', compact('documentNumber', 'lines', 'invoice', 'canDeductInventory', 'deductableLines'));
    }

    /**
     * Stream the printable PDF for one posted sales invoice.
     *
     * @param  string  $documentNumber  The document number of the invoice.
     * @return Response The streamed history invoice PDF.
     */
    public function pdf(string $documentNumber): Response
    {
        $this->allows(FileResources::SALES_INVOICES);

        /** @var SalesInvoice|null $invoice */
        $invoice = SalesInvoice::query()
            ->with(['company', 'contact', 'lines.product'])
            ->where('document_number', $documentNumber)
            ->where('status', DocumentStatus::POSTED)
            ->first();

        abort_if($invoice === null, 404);

        /** @var Collection<int, SalesInvoiceLine> $lines */
        $lines = $invoice->lines;

        /** @var Collection<int, ArLedger> $payments */
        $payments = ArLedger::query()->againstReceivableAccount()
            ->where('type', LedgerType::PAYMENT->value)
            ->where('reference_number', $documentNumber)
            ->orderBy('date')
            ->get();

        $amountPaid = $payments->reduce(
            static fn (string $carry, ArLedger $payment): string => bcadd(
                $carry,
                bcsub((string) $payment->credit_amount, (string) $payment->debit_amount, 2),
                2
            ),
            '0.00'
        );
        $amountPaid = bccomp($amountPaid, '0.00', 2) < 0 ? '0.00' : $amountPaid;

        $balanceDue = bcsub(bcadd((string) $invoice->total_amount, '0', 2), $amountPaid, 2);
        $balanceDue = bccomp($balanceDue, '0.00', 2) < 0 ? '0.00' : $balanceDue;

        return Pdf::loadView('sales.invoices.history.pdf', [
            'documentNumber' => $documentNumber,
            'invoice' => $invoice,
            'lines' => $lines,
            'payments' => $payments,
            'amountPaid' => $amountPaid,
            'balanceDue' => $balanceDue,
            'setting' => Setting::query()->firstOrNew(),
        ])->setPaper('a4')->stream($documentNumber.'.pdf');
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
            ->where('status', DocumentStatus::POSTED)
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
}
