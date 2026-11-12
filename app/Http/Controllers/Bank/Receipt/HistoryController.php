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
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * Posted receipts. A posted receipt is an event that happened, so nothing here
 * changes it. The page exists to show what was recorded, when it happened, and
 * where the money moved.
 */
class HistoryController extends Controller
{
    /**
     * Display a listing of posted receipts.
     *
     * @param  Request  $request  The incoming request containing list filters.
     * @return View Returns the rendered Blade view.
     */
    public function index(Request $request): View
    {
        $this->allows(FileResources::RECEIPTS_HISTORY);

        /** @var string $search */
        $search = $request->input('search', '');
        $search = trim($search);
        /** @var string $startDate */
        $startDate = $request->input('start_date') ?? '';
        /** @var string $endDate */
        $endDate = $request->input('end_date') ?? '';

        $receipts = Receipt::query()
            ->where('status', DocumentStatus::POSTED)
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

        return view('bank.receipt.history.index', compact('receipts', 'search', 'startDate', 'endDate'));
    }

    /**
     * Display the specified posted receipt.
     *
     * @param  string  $documentNumber  The document number of the receipt.
     * @return View Returns the rendered Blade view.
     */
    public function show(string $documentNumber): View
    {
        $this->allows(FileResources::RECEIPTS_HISTORY);

        $receipt = Receipt::query()
            ->where('document_number', $documentNumber)
            ->where('status', DocumentStatus::POSTED)
            ->firstOrFail();

        /** @var Collection<int, \App\Models\Bank\BankLedger> $bankLedgers */
        $bankLedgers = BankLedger::query()
            ->where('document_number', $documentNumber)
            ->get();

        /** @var Collection<int, \App\Models\Bank\CashLedger> $cashLedgers */
        $cashLedgers = CashLedger::query()
            ->where('document_number', $documentNumber)
            ->get();

        /** @var Collection<int, \App\Models\Bank\EWalletLedger> $eWalletLedgers */
        $eWalletLedgers = EWalletLedger::query()
            ->where('document_number', $documentNumber)
            ->get();

        /** @var Collection<int, \App\Models\Crm\ArLedger> $arLedgers */
        $arLedgers = ArLedger::query()
            ->where('document_number', $documentNumber)
            ->get();

        return view('bank.receipt.history.show', compact(
            'receipt',
            'documentNumber',
            'bankLedgers',
            'cashLedgers',
            'eWalletLedgers',
            'arLedgers',
        ));
    }

    /**
     * Print one posted receipt.
     *
     * @param  string  $documentNumber  The document number of the receipt.
     * @return Response Returns the PDF download response.
     */
    public function pdf(string $documentNumber): Response
    {
        $this->allows(FileResources::RECEIPTS_HISTORY);

        $receipt = Receipt::query()
            ->where('document_number', $documentNumber)
            ->where('status', DocumentStatus::POSTED)
            ->firstOrFail();

        return Pdf::loadView('bank.receipt.pdf', [
            'receipt' => $receipt,
            'documentNumber' => $documentNumber,
            'setting' => Setting::query()->first(),
        ])
            ->setPaper('a4')
            ->stream("receipt_$documentNumber.pdf");
    }
}
