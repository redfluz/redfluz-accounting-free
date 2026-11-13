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
use App\Enums\Finance\DocumentStatus;
use App\Enums\Finance\LedgerType;
use App\Http\Controllers\Controller;
use App\Models\Crm\ApLedger;
use App\Models\Finance\Ledger;
use App\Models\Purchase\PurchaseInvoice;
use App\Models\Purchase\PurchaseInvoiceLine;
use App\Models\System\Setting;
use App\Services\DecimalService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Class HistoryController
 *
 * Lists, views, and exports posted purchase invoices.
 */
class HistoryController extends Controller
{
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

        $entries = PurchaseInvoice::query()
            ->with(['company'])
            ->where('status', DocumentStatus::POSTED)
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
            ->orderByDesc('date')
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
            ->where('status', DocumentStatus::POSTED)
            ->first();

        if ($invoice === null) {
            abort(404);
        }

        /** @var Collection<int, Ledger> $glLedgers */
        $glLedgers = Ledger::query()
            ->where('document_number', $documentNumber)
            ->orderBy('id')
            ->get();

        /** @var Collection<int, ApLedger> $apLedgers */
        $apLedgers = ApLedger::query()
            ->where('document_number', $documentNumber)
            ->orderBy('id')
            ->get();

        $lines = $invoice->lines;

        return view('purchase.invoices.history.show', array_merge(
            compact('documentNumber', 'lines', 'invoice'),
            ['glLedgers' => $glLedgers, 'apLedgers' => $apLedgers],
            ['postingDateMin' => null, 'postingDateMax' => null],
        ));
    }

    /**
     * Stream the printable PDF for one posted purchase invoice.
     *
     * @param  string  $documentNumber  The document number of the invoice.
     * @return Response The streamed history invoice PDF.
     */
    public function pdf(string $documentNumber): Response
    {
        $this->allows(FileResources::PURCHASE_INVOICES);

        $invoice = PurchaseInvoice::query()
            ->with(['company', 'contact', 'lines.product'])
            ->where('document_number', $documentNumber)
            ->where('status', DocumentStatus::POSTED)
            ->first();

        abort_if($invoice === null, 404);

        /** @var Collection<int, PurchaseInvoiceLine> $lines */
        $lines = $invoice->lines;

        /** @var Collection<int, ApLedger> $payments */
        $payments = ApLedger::query()->againstPayableAccount()
            ->where('type', LedgerType::PAYMENT->value)
            ->where('reference_number', $documentNumber)
            ->orderBy('date')
            ->get();

        $amountPaid = $payments->reduce(
            /**
             * @param  numeric-string  $carry
             * @return numeric-string
             */
            static function (string $carry, ApLedger $payment): string {
                $diff = bcsub((string) $payment->debit_amount, (string) $payment->credit_amount, 2);

                return bcadd($carry, $diff, 2);
            },
            '0.00'
        );
        $amountPaid = bccomp($amountPaid, '0.00', 2) < 0 ? '0.00' : $amountPaid;

        /** @var numeric-string $invoiceTotal */
        $invoiceTotal = DecimalService::normalize((string) $invoice->total_amount);
        $balanceDue = bcsub(bcadd($invoiceTotal, '0.00', 2), $amountPaid, 2);
        $balanceDue = bccomp($balanceDue, '0.00', 2) < 0 ? '0.00' : $balanceDue;

        return Pdf::loadView('purchase.invoices.history.pdf', [
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
            ->where('status', DocumentStatus::POSTED)
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
}
