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

namespace app\Http\Controllers\Bank\Transfer;

use App\Enums\FileResources;
use App\Enums\Finance\DocumentStatus;
use App\Http\Controllers\Controller;
use App\Models\Bank\Transfer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Throwable;

/**
 * Cancelled bank transfers, and the one thing that can be done to them.
 *
 * A cancelled transfer is kept rather than removed: it is part of the record of
 * what was asked for and what happened to it. The only action here is restoring
 * one. A cancelled transfer has not moved money, so restoring it returns it to
 * pending and it can be approved as though it had never been cancelled. Who
 * restored it, and when, is recorded.
 */
class CancelledController extends Controller
{
    /**
     * The permission resource for this controller.
     */
    private function resource(): FileResources
    {
        return FileResources::BANK_TRANSFERS_CANCELLED;
    }

    /**
     * Display a listing of cancelled transfers.
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

        $transfers = Transfer::query()
            ->with(['bankAccount.chart', 'transitChart'])
            ->where('direction', Transfer::DIRECTION_IN)
            ->where('status', DocumentStatus::CANCELLED)
            ->when($search !== '', fn (Builder $query) => $query->where(
                fn (Builder $q) => $q->where('document_number', 'like', "%$search%")
                    ->orWhere('description', 'like', "%$search%")
                    ->orWhere('reference_number', 'like', "%$search%")
            ))
            ->when(filled($startDate), fn (Builder $query) => $query->whereDate('date', '>=', $startDate))
            ->when(filled($endDate), fn (Builder $query) => $query->whereDate('date', '<=', $endDate))
            ->orderByDesc('date')
            ->orderByDesc('document_number')
            ->paginate(self::PER_PAGE_GRID_NORMAL)
            ->withQueryString();

        return view('bank.bank-transfers.cancelled.index', compact('transfers', 'search', 'startDate', 'endDate'));
    }

    /**
     * Display the specified cancelled transfer.
     *
     * @param  string  $documentNumber  The document number of the transfer.
     * @return View Returns the rendered Blade view.
     */
    public function show(string $documentNumber): View
    {
        $this->allows($this->resource());

        $transfer = Transfer::query()
            ->with(['bankAccount.chart', 'transitChart'])
            ->where('document_number', $documentNumber)
            ->where('direction', Transfer::DIRECTION_IN)
            ->where('status', DocumentStatus::CANCELLED)
            ->firstOrFail();

        return view('bank.bank-transfers.cancelled.show', compact('transfer', 'documentNumber'));
    }

    /**
     * Restore a cancelled transfer, putting it back in the pending list.
     *
     * @param  string  $documentNumber  The document number of the transfer.
     * @return RedirectResponse Redirect response to the pending transfer list.
     *
     * @throws Throwable
     */
    public function uncancel(string $documentNumber): RedirectResponse
    {
        $this->allows($this->resource());

        DB::transaction(function () use ($documentNumber): void {
            $transfer = Transfer::query()
                ->where('document_number', $documentNumber)
                ->where('direction', Transfer::DIRECTION_IN)
                ->where('status', DocumentStatus::CANCELLED)
                ->lockForUpdate()
                ->first();

            abort_if($transfer === null, 404);

            $transfer->update(['status' => DocumentStatus::PENDING]);

            $this->attributeUndone($transfer);
        });

        return redirect()
            ->route('bank.bank-transfers.index')
            ->with('success', __('common.messages.uncancelled'));
    }
}
