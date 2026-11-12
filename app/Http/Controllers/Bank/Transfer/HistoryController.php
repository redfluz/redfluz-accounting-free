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
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Posted bank transfers. A posted transfer is money the bank has already
 * credited, so nothing here changes it.
 */
class HistoryController extends Controller
{
    /**
     * The permission resource for this controller.
     */
    private function resource(): FileResources
    {
        return FileResources::BANK_TRANSFERS_HISTORY;
    }

    /**
     * Display a listing of posted transfers.
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
            ->where('status', DocumentStatus::POSTED)
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

        return view('bank.bank-transfers.history.index', compact('transfers', 'search', 'startDate', 'endDate'));
    }

    /**
     * Display the specified posted transfer.
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
            ->where('status', DocumentStatus::POSTED)
            ->firstOrFail();

        return view('bank.bank-transfers.history.show', compact('transfer', 'documentNumber'));
    }
}
