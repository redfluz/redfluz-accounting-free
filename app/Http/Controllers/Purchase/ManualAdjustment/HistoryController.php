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

namespace App\Http\Controllers\Purchase\ManualAdjustment;

use App\Enums\FileResources;
use App\Enums\Finance\DocumentStatus;
use App\Http\Controllers\Controller;
use App\Models\Crm\ApLedger;
use App\Models\Finance\Ledger;
use App\Models\Purchase\ManualPurchaseAdjustment;
use App\Models\System\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Class HistoryController
 *
 * Lists, views, and exports posted manual purchase adjustments. History pages
 * are read-only records of what has been posted. Manual adjustments print from
 * chart accounts rather than product lines, so they keep their own history
 * pages instead of sharing the linked adjustment's.
 */
class HistoryController extends Controller
{
    /**
     * Display a listing of posted manual purchase adjustments.
     *
     * @param  Request  $request  The incoming request containing list filters.
     * @return View Returns the rendered Blade view.
     */
    public function index(Request $request): View
    {
        $this->allows(FileResources::MANUAL_PURCHASE_ADJUSTMENTS);

        $search = $request->string('search')->toString();
        $startDate = $request->string('start_date')->toString();
        $endDate = $request->string('end_date')->toString();

        $entries = ManualPurchaseAdjustment::query()
            ->with(['company'])
            ->where('status', DocumentStatus::POSTED)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('document_number', 'like', "%$search%")
                        ->orWhere('reference_number', 'like', "%$search%")
                        ->orWhere('company_name', 'like', "%$search%")
                        ->orWhere('notes', 'like', "%$search%")
                        ->orWhereHas('charts', fn ($chartQuery) => $chartQuery->where('description', 'like', "%$search%"));
                });
            })
            ->when($startDate !== '', fn ($query) => $query->whereDate('date', '>=', $startDate))
            ->when($endDate !== '', fn ($query) => $query->whereDate('date', '<=', $endDate))
            ->orderByDesc('date')
            ->orderByDesc('created_at')
            ->paginate(self::PER_PAGE_GRID_NORMAL)
            ->withQueryString();

        return view('purchase.manual-adjustments.history.index', compact(
            'entries',
            'search',
            'startDate',
            'endDate',
        ));
    }

    /**
     * Display the specified posted manual purchase adjustment.
     *
     * @param  string  $documentNumber  The document number of the posted manual adjustment.
     * @return View Returns the rendered Blade view.
     */
    public function show(string $documentNumber): View
    {
        $this->allows(FileResources::MANUAL_PURCHASE_ADJUSTMENTS);

        /** @var ManualPurchaseAdjustment|null $adjustment */
        $adjustment = ManualPurchaseAdjustment::query()
            ->with(['company', 'charts'])
            ->where('document_number', $documentNumber)
            ->where('status', DocumentStatus::POSTED)
            ->first();

        abort_if($adjustment === null, 404);

        $reversalNumber = ManualPurchaseAdjustment::query()
            ->where('document_original', $adjustment->document_number)
            ->value('document_number');

        /** @var Collection<int, ApLedger> $apLedgers */
        $apLedgers = ApLedger::query()
            ->where('document_number', $documentNumber)
            ->orderBy('id')
            ->get();

        /** @var Collection<int, Ledger> $glLedgers */
        $glLedgers = Ledger::query()
            ->where('document_number', $documentNumber)
            ->orderBy('id')
            ->get();

        return view('purchase.manual-adjustments.history.show', [
            'adjustment' => $adjustment,
            'documentNumber' => $documentNumber,
            'reversalNumber' => $reversalNumber,
            'apLedgers' => $apLedgers,
            'glLedgers' => $glLedgers,
        ]);
    }

    /**
     * Print one posted manual purchase adjustment.
     *
     * @param  string  $documentNumber  The document number of the posted manual adjustment.
     * @return Response Returns the PDF download response.
     */
    public function pdf(string $documentNumber): Response
    {
        $this->allows(FileResources::MANUAL_PURCHASE_ADJUSTMENTS);

        /** @var ManualPurchaseAdjustment|null $adjustment */
        $adjustment = ManualPurchaseAdjustment::query()
            ->with(['company', 'charts'])
            ->where('document_number', $documentNumber)
            ->where('status', DocumentStatus::POSTED)
            ->first();

        abort_if($adjustment === null, 404);

        return Pdf::loadView('purchase.manual-adjustments.pdf', [
            'adjustment' => $adjustment,
            'documentNumber' => $documentNumber,
            'setting' => Setting::query()->first(),
        ])
            ->setPaper('a4')
            ->stream("manual_purchase_adjustment_$documentNumber.pdf");
    }
}
