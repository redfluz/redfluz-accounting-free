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

namespace app\Http\Controllers\Sales\Invoice;

use App\Enums\FileAction;
use App\Enums\FileResources;
use App\Enums\Finance\DocumentStatus;
use App\Enums\Finance\LedgerType;
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
 * Class CancelledController
 *
 * Lists, views, restores, and exports cancelled sales invoices.
 */
class CancelledController extends Controller
{
    /**
     * Display a listing of cancelled sales invoices.
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
            ->with(['company'])
            ->where('status', DocumentStatus::CANCELLED)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('document_number', 'like', "%$search%")
                        ->orWhere('reference_number', 'like', "%$search%")
                        ->orWhereHas('company', fn ($q) => $q->where('name', 'like', "%$search%"));
                });
            })
            ->when($startDate !== '', fn ($query) => $query->whereDate('date', '>=', $startDate))
            ->when($endDate !== '', fn ($query) => $query->whereDate('date', '<=', $endDate))
            ->orderByDesc('date')
            ->paginate(self::PER_PAGE_GRID_NORMAL)
            ->withQueryString();

        return view('sales.invoices.cancelled.index', compact(
            'entries',
            'search',
            'startDate',
            'endDate',
        ));
    }

    /**
     * Display the specified cancelled sales invoice.
     *
     * @param  string  $documentNumber  The document number of the cancelled invoice.
     * @return View|RedirectResponse The rendered page, or a redirect to the live record.
     */
    public function show(string $documentNumber): View|RedirectResponse
    {
        $this->allows(FileResources::SALES_INVOICES);

        /** @var SalesInvoice|null $invoice */
        $invoice = SalesInvoice::query()
            ->with(['company', 'contact', 'lines.product'])
            ->where('document_number', $documentNumber)
            ->first();

        abort_if($invoice === null, 404);

        /** @var Collection<int, SalesInvoiceLine> $lines */
        $lines = $invoice->lines;

        // This page explains a cancelled document. Once restored it no longer
        // belongs here, and its header badge is hardcoded to say Cancelled.
        if ($invoice->status !== DocumentStatus::CANCELLED) {
            return redirect()
                ->route('sales.invoices.show', $documentNumber)
                ->with('info', __('common.messages.no_longer_cancelled'));
        }

        return view('sales.invoices.cancelled.show', compact('documentNumber', 'invoice', 'lines'));
    }

    /**
     * Stream a PDF for a cancelled sales invoice.
     *
     * @param  string  $documentNumber  The document number of the cancelled invoice.
     * @return Response The streamed invoice PDF.
     */
    public function pdf(string $documentNumber): Response
    {
        $this->allows(FileResources::SALES_INVOICES);

        /** @var SalesInvoice|null $invoice */
        $invoice = SalesInvoice::query()
            ->with(['company', 'contact', 'lines.product', 'quotation', 'salesOrder'])
            ->where('document_number', $documentNumber)
            ->where('status', DocumentStatus::CANCELLED)
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

        return Pdf::loadView('sales.invoices.cancelled.pdf', [
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
     * Restore a cancelled sales invoice to pending status.
     *
     * @param  string  $documentNumber  The document number of the cancelled invoice.
     * @return RedirectResponse Redirects to the cancelled invoices index.
     */
    public function uncancel(string $documentNumber): RedirectResponse
    {
        $this->allows(FileResources::SALES_INVOICES, FileAction::APPROVE);

        $restored = SalesInvoice::query()
            ->where('document_number', $documentNumber)
            ->where('status', DocumentStatus::CANCELLED)
            ->update(['status' => DocumentStatus::PENDING]);

        // A repeat click matches nothing, because the rows are already pending.
        // Report that and redirect, rather than claiming a restore that never
        // happened and leaving the button on screen to be pressed again.
        if ($restored === 0) {
            return redirect()
                ->route('sales.invoices.cancelled.index')
                ->with('error', __('common.messages.nothing_to_uncancel'));
        }

        return redirect()
            ->route('sales.invoices.cancelled.index')
            ->with('success', __('common.messages.uncancelled'));
    }
}
