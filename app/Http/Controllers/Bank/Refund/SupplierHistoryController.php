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
use App\Enums\Finance\DocumentStatus;
use App\Http\Controllers\Controller;
use App\Models\Bank\Refund\SupplierRefund;
use App\Models\Crm\ApLedger;
use App\Models\System\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Posted supplier refunds. A posted refund is an event that happened, so nothing
 * here changes it.
 */
class SupplierHistoryController extends Controller
{
    /**
     * The permission resource for this controller.
     */
    private function resource(): FileResources
    {
        return FileResources::SUPPLIER_REFUNDS_HISTORY;
    }

    /**
     * Display a listing of posted refunds.
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

        $refunds = SupplierRefund::query()
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

        return view('bank.supplier-refund.history.index', compact('refunds', 'search', 'startDate', 'endDate'));
    }

    /**
     * Display the specified posted refund.
     *
     * @param  string  $documentNumber  The document number of the refund.
     * @return View Returns the rendered Blade view.
     */
    public function show(string $documentNumber): View
    {
        $this->allows($this->resource());

        $refund = SupplierRefund::query()
            ->where('document_number', $documentNumber)
            ->where('status', DocumentStatus::POSTED)
            ->firstOrFail();

        /** @var Collection<int, ApLedger> $ledgers */
        $ledgers = ApLedger::query()
            ->where('document_number', $documentNumber)
            ->get();

        return view('bank.supplier-refund.history.show', compact('refund', 'documentNumber', 'ledgers'));
    }

    /**
     * Print one posted refund.
     *
     * @param  string  $documentNumber  The document number of the refund.
     * @return Response Returns the PDF download response.
     */
    public function pdf(string $documentNumber): Response
    {
        $this->allows($this->resource());

        $refund = SupplierRefund::query()
            ->where('document_number', $documentNumber)
            ->where('status', DocumentStatus::POSTED)
            ->firstOrFail();

        return Pdf::loadView('bank.supplier-refund.pdf', [
            'refund' => $refund,
            'documentNumber' => $documentNumber,
            'setting' => Setting::query()->first(),
        ])
            ->setPaper('a4')
            ->stream("refund_$documentNumber.pdf");
    }
}
