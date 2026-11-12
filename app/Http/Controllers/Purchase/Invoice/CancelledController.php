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

namespace app\Http\Controllers\Purchase\Invoice;

use App\Enums\FileAction;
use App\Enums\FileResources;
use App\Enums\Finance\DocumentStatus;
use App\Enums\Finance\LedgerType;
use App\Http\Controllers\Controller;
use App\Models\Crm\ApLedger;
use App\Models\Purchase\PurchaseInvoice;
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
 * Lists, views, restores, and exports cancelled purchase invoices.
 */
class CancelledController extends Controller
{
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

        $entries = PurchaseInvoice::query()
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
            ->where('status', DocumentStatus::CANCELLED)
            ->exists();
    }

    /**
     * Stream a PDF for a cancelled purchase invoice.
     *
     * @param  string  $documentNumber  The document number of the cancelled invoice.
     * @return Response The streamed invoice PDF.
     */
    public function pdf(string $documentNumber): Response
    {
        $this->allows(FileResources::PURCHASE_INVOICES);

        $invoice = PurchaseInvoice::query()
            ->with(['company', 'contact', 'lines.product'])
            ->where('document_number', $documentNumber)
            ->where('status', DocumentStatus::CANCELLED)
            ->first();

        abort_if($invoice === null, 404);

        $lines = $invoice->lines;

        /** @var Collection<int, ApLedger> $payments */
        $payments = ApLedger::query()->againstPayableAccount()
            ->where('type', LedgerType::PAYMENT->value)
            ->where('reference_number', $documentNumber)
            ->orderBy('date')
            ->get();

        $amountPaid = $payments->reduce(
            static fn (string $carry, ApLedger $payment): string => bcadd(
                $carry,
                bcsub((string) $payment->debit_amount, (string) $payment->credit_amount, 2),
                2
            ),
            '0.00'
        );
        $amountPaid = bccomp($amountPaid, '0.00', 2) < 0 ? '0.00' : $amountPaid;

        $balanceDue = bcsub(bcadd((string) $invoice->total_amount, '0', 2), $amountPaid, 2);
        $balanceDue = bccomp($balanceDue, '0.00', 2) < 0 ? '0.00' : $balanceDue;

        return Pdf::loadView('purchase.invoices.cancelled.pdf', [
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
            ->where('status', DocumentStatus::CANCELLED)
            ->update(['status' => DocumentStatus::PENDING]);

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
}
