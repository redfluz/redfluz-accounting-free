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

namespace app\Http\Controllers\Bank\Receipt;

use App\Enums\FileResources;
use App\Enums\Finance\DocumentStatus;
use App\Http\Controllers\Controller;
use App\Models\Bank\Receipt;
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
 * Cancelled receipts, and the one thing that can be done to them.
 *
 * A cancelled receipt is kept rather than removed. It is part of the record of
 * what was asked for and what happened to it, and deleting it would leave the
 * approval trail with a gap where a decision used to be.
 *
 * The only action here is restoring one. A cancelled receipt has not moved money,
 * so restoring it returns it to pending and it can be posted as though it had
 * never been cancelled. Who restored it, and when, is recorded, because a
 * receipt that was cancelled and then brought back is a document somebody looked
 * at twice.
 */
class CancelledController extends Controller
{
    /**
     * Display a listing of cancelled receipts.
     *
     * @param  Request  $request  The incoming request containing list filters.
     * @return View Returns the rendered Blade view.
     */
    public function index(Request $request): View
    {
        $this->allows(FileResources::RECEIPTS_CANCELLED);

        /** @var string $search */
        $search = $request->input('search', '');
        $search = trim($search);
        /** @var string $startDate */
        $startDate = $request->input('start_date') ?? '';
        /** @var string $endDate */
        $endDate = $request->input('end_date') ?? '';

        $receipts = Receipt::query()
            ->where('status', DocumentStatus::CANCELLED)
            ->when($search !== '', fn (Builder $query) => $query->where(
                fn (Builder $q) => $q->where('document_number', 'like', "%$search%")
                    ->orWhere('reference_number', 'like', "%$search%")
                    ->orWhere('company_name', 'like', "%$search%")
            ))
            ->when(filled($startDate), fn (Builder $query) => $query->whereDate('date', '>=', $startDate))
            ->when(filled($endDate), fn (Builder $query) => $query->whereDate('date', '<=', $endDate))
            ->orderByDesc('date')
            ->orderByDesc('document_number')
            ->paginate(self::PER_PAGE_GRID_NORMAL)
            ->withQueryString();

        return view('bank.receipt.cancelled.index', compact('receipts', 'search', 'startDate', 'endDate'));
    }

    /**
     * Display the specified cancelled receipt.
     *
     * @param  string  $documentNumber  The document number of the receipt.
     * @return View Returns the rendered Blade view.
     */
    public function show(string $documentNumber): View
    {
        $this->allows(FileResources::RECEIPTS_CANCELLED);

        $receipt = Receipt::query()
            ->where('document_number', $documentNumber)
            ->where('status', DocumentStatus::CANCELLED)
            ->firstOrFail();

        return view('bank.receipt.cancelled.show', compact('receipt', 'documentNumber'));
    }

    /**
     * Print one cancelled receipt.
     *
     * @param  string  $documentNumber  The document number of the receipt.
     * @return Response Returns the PDF download response.
     */
    public function pdf(string $documentNumber): Response
    {
        $this->allows(FileResources::RECEIPTS_CANCELLED);

        $receipt = Receipt::query()
            ->where('document_number', $documentNumber)
            ->where('status', DocumentStatus::CANCELLED)
            ->firstOrFail();

        return Pdf::loadView('bank.receipt.pdf', [
            'receipt' => $receipt,
            'documentNumber' => $documentNumber,
            'setting' => Setting::query()->first(),
        ])
            ->setPaper('a4')
            ->stream("receipt_$documentNumber.pdf");
    }

    /**
     * Restore a cancelled receipt, putting it back in the pending list.
     *
     * @param  string  $documentNumber  The document number of the receipt.
     * @return RedirectResponse Redirect response to the pending receipt list.
     *
     * @throws Throwable
     */
    public function uncancel(string $documentNumber): RedirectResponse
    {
        $this->allows(FileResources::RECEIPTS_CANCELLED);

        DB::transaction(function () use ($documentNumber): void {
            $receipt = Receipt::query()
                ->where('document_number', $documentNumber)
                ->where('status', DocumentStatus::CANCELLED)
                ->lockForUpdate()
                ->first();

            // A second click matches nothing, because the receipt is no longer
            // cancelled. Reporting that and redirecting is better than claiming
            // a restore that did not happen.
            abort_if($receipt === null, 404);

            $receipt->update(['status' => DocumentStatus::PENDING]);

            $this->attributeUndone($receipt);
        });

        return redirect()
            ->route('bank.receipts.index')
            ->with('success', __('common.messages.uncancelled'));
    }
}
