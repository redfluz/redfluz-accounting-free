<?php

/**
 * ============================================================================
 *
 * @author      redfluz
 *
 * @github      https://github.com/redfluz
 *
 * @youtube     https://www.youtube.com/@redfluz
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

namespace App\Http\Controllers\Bank\Refund;

use App\Enums\FileResources;
use App\Enums\LedgerStatus;
use App\Http\Controllers\Controller;
use App\Models\Bank\Refund\SupplierRefund;
use App\Models\System\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Throwable;

/**
 * Cancelled refunds, and the one thing that can be done to them.
 *
 * A cancelled refund is kept rather than removed. It is part of the record of
 * what was asked for and what happened to it, and deleting it would leave the
 * approval trail with a gap where a decision used to be.
 *
 * The only action here is restoring one. A cancelled refund has not moved money,
 * so restoring it returns it to pending and it can be posted as though it had
 * never been cancelled. Who restored it, and when, is recorded, because a
 * receipt that was cancelled and then brought back is a document somebody looked
 * at twice.
 */
class SupplierCancelledController extends Controller
{
    /**
     * The permission resource for this controller.
     */
    private function resource(): FileResources
    {
        return FileResources::SUPPLIER_REFUNDS_CANCELLED;
    }

    /**
     * Display a listing of cancelled refunds.
     *
     * @param  Request  $request  The incoming request containing list filters.
     * @return View Returns the rendered Blade view.
     */
    public function index(Request $request): View
    {
        $this->allows($this->resource());

        /** @var string $search */
        $search = $request->input('search', '');
        $search = trim($search);
        /** @var string $startDate */
        $startDate = $request->input('start_date') ?? '';
        /** @var string $endDate */
        $endDate = $request->input('end_date') ?? '';

        $refunds = $this->buildQuery($search, $startDate, $endDate)
            ->paginate(self::PER_PAGE_GRID_NORMAL)
            ->withQueryString();

        return view('bank.supplier-refund.cancelled.index', compact('refunds', 'search', 'startDate', 'endDate'));
    }

    /**
     * Display the specified cancelled refund.
     *
     * @param  string  $documentNumber  The document number of the refund.
     * @return View Returns the rendered Blade view.
     */
    public function show(string $documentNumber): View
    {
        $this->allows($this->resource());

        $refund = SupplierRefund::query()
            ->where('document_number', $documentNumber)
            ->where('status', LedgerStatus::CANCELLED)
            ->with('lines')
            ->firstOrFail();

        return view('bank.supplier-refund.cancelled.show', compact('refund', 'documentNumber'));
    }

    /**
     * Print one cancelled refund.
     *
     * @param  string  $documentNumber  The document number of the refund.
     * @return Response Returns the PDF download response.
     */
    public function pdf(string $documentNumber): Response
    {
        $this->allows($this->resource());

        $refund = SupplierRefund::query()
            ->where('document_number', $documentNumber)
            ->where('status', LedgerStatus::CANCELLED)
            ->with('lines')
            ->firstOrFail();

        return Pdf::loadView('bank.supplier-refund.pdf', [
            'refund' => $refund,
            'documentNumber' => $documentNumber,
            'setting' => Setting::query()->first(),
        ])
            ->setPaper('a4')
            ->download("refund_{$documentNumber}.pdf");
    }

    /**
     * Print the whole cancelled listing.
     *
     * @param  Request  $request  The incoming request containing list filters.
     * @return Response Returns the PDF download response.
     */
    public function exportPdf(Request $request): Response
    {
        $this->allows($this->resource());

        /** @var string $search */
        $search = $request->input('search', '');
        $search = trim($search);
        /** @var string $startDate */
        $startDate = $request->input('start_date') ?? '';
        /** @var string $endDate */
        $endDate = $request->input('end_date') ?? '';

        return Pdf::loadView('bank.supplier-refund.cancelled.pdf', [
            'refunds' => $this->buildQuery($search, $startDate, $endDate)->get(),
            'startDate' => $startDate,
            'endDate' => $endDate,
            'setting' => Setting::query()->first(),
        ])
            ->setPaper('a4', 'landscape')
            ->download('cancelled_refunds_'.now()->format('Ymd_His').'.pdf');
    }

    /**
     * Restore a cancelled refund, putting it back in the pending list.
     *
     * @param  string  $documentNumber  The document number of the refund.
     * @return RedirectResponse Redirect response to the pending refund list.
     *
     * @throws Throwable
     */
    public function uncancel(string $documentNumber): RedirectResponse
    {
        $this->allows($this->resource());

        DB::transaction(function () use ($documentNumber): void {
            $refund = SupplierRefund::query()
                ->where('document_number', $documentNumber)
                ->where('status', LedgerStatus::CANCELLED)
                ->lockForUpdate()
                ->first();

            // A second click matches nothing, because the receipt is no longer
            // cancelled. Reporting that and redirecting is better than claiming
            // a restore that did not happen.
            abort_if($refund === null, 404);

            $refund->update(['status' => LedgerStatus::PENDING]);

            $this->attributeUndone($refund);
        });

        return redirect()
            ->route('bank.receipts.index')
            ->with('success', __('common.messages.uncancelled'));
    }

    /**
     * The cancelled refunds matching the filters.
     *
     * @param  string  $search  Free text across the number, reference and customer.
     * @param  string|null  $startDate  Earliest document date.
     * @param  string|null  $endDate  Latest document date.
     * @return Builder<SupplierRefund>
     */
    private function buildQuery(string $search, ?string $startDate, ?string $endDate): Builder
    {
        return SupplierRefund::query()
            ->where('status', LedgerStatus::CANCELLED)
            ->when($search !== '', fn (Builder $query) => $query->where(
                fn (Builder $q) => $q->where('document_number', 'like', "%$search%")
                    ->orWhere('reference_number', 'like', "%$search%")
                    ->orWhere('company_name', 'like', "%$search%")
            ))
            ->when(filled($startDate), fn (Builder $query) => $query->whereDate('date', '>=', $startDate))
            ->when(filled($endDate), fn (Builder $query) => $query->whereDate('date', '<=', $endDate))
            ->orderByDesc('date')
            ->orderByDesc('document_number');
    }
}
