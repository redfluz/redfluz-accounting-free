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

namespace app\Http\Controllers\Purchase\ManualAdjustment;

use App\Enums\FileAction;
use App\Enums\FileResources;
use App\Enums\Finance\DocumentStatus;
use App\Http\Controllers\Controller;
use App\Models\Purchase\ManualPurchaseAdjustment;
use App\Models\System\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Class CancelledController
 *
 * Lists, views, restores, and exports cancelled manual purchase adjustments.
 * Manual adjustments print from chart accounts rather than product lines, so
 * they keep their own cancelled pages instead of sharing the linked
 * adjustment's.
 */
class CancelledController extends Controller
{
    /**
     * Display a listing of cancelled manual purchase adjustments.
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
            ->where('status', DocumentStatus::CANCELLED)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('document_number', 'like', "%$search%")
                        ->orWhere('reference_number', 'like', "%$search%")
                        ->orWhere('company_name', 'like', "%$search%")
                        ->orWhere('notes', 'like', "%$search%");
                });
            })
            ->when($startDate !== '', fn ($query) => $query->whereDate('date', '>=', $startDate))
            ->when($endDate !== '', fn ($query) => $query->whereDate('date', '<=', $endDate))
            ->orderByDesc('date')
            ->orderByDesc('created_at')
            ->paginate(self::PER_PAGE_GRID_NORMAL)
            ->withQueryString();

        return view('purchase.manual-adjustments.cancelled.index', compact(
            'entries',
            'search',
            'startDate',
            'endDate',
        ));
    }

    /**
     * Display the specified cancelled manual purchase adjustment.
     *
     * @param  string  $documentNumber  The document number of the cancelled manual adjustment.
     * @return View|RedirectResponse The rendered page, or a redirect to the live record.
     */
    public function show(string $documentNumber): View|RedirectResponse
    {
        $this->allows(FileResources::MANUAL_PURCHASE_ADJUSTMENTS);

        /** @var ManualPurchaseAdjustment|null $adjustment */
        $adjustment = ManualPurchaseAdjustment::query()
            ->with(['company', 'charts'])
            ->where('document_number', $documentNumber)
            ->first();

        abort_if($adjustment === null, 404);

        // This page explains a cancelled document. Once restored it no longer
        // belongs here, and its header badge is hardcoded to say Cancelled.
        if ($adjustment->status !== DocumentStatus::CANCELLED) {
            return redirect()
                ->route('purchase.manual-adjustments.show', $documentNumber)
                ->with('info', __('common.messages.no_longer_cancelled'));
        }

        return view('purchase.manual-adjustments.cancelled.show', [
            'adjustment' => $adjustment,
            'documentNumber' => $documentNumber,
        ]);
    }

    /**
     * Print one cancelled manual purchase adjustment.
     *
     * @param  string  $documentNumber  The document number of the cancelled manual adjustment.
     * @return Response Returns the PDF download response.
     */
    public function pdf(string $documentNumber): Response
    {
        $this->allows(FileResources::MANUAL_PURCHASE_ADJUSTMENTS);

        /** @var ManualPurchaseAdjustment|null $adjustment */
        $adjustment = ManualPurchaseAdjustment::query()
            ->with(['company', 'charts'])
            ->where('document_number', $documentNumber)
            ->where('status', DocumentStatus::CANCELLED)
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

    /**
     * Restore a cancelled manual purchase adjustment to pending status.
     *
     * @param  string  $documentNumber  The document number of the cancelled manual adjustment.
     * @return RedirectResponse Redirects to the cancelled manual purchase adjustments index.
     */
    public function uncancel(string $documentNumber): RedirectResponse
    {
        $this->allows(FileResources::MANUAL_PURCHASE_ADJUSTMENTS, FileAction::APPROVE);

        $restored = ManualPurchaseAdjustment::query()
            ->where('document_number', $documentNumber)
            ->where('status', DocumentStatus::CANCELLED)
            ->update(['status' => DocumentStatus::PENDING]);

        // A repeat click matches nothing, because the rows are already pending.
        // Report that and redirect, rather than claiming a restore that never
        // happened and leaving the button on screen to be pressed again.
        if ($restored === 0) {
            return redirect()
                ->route('purchase.manual-adjustments.cancelled.index')
                ->with('error', __('common.messages.nothing_to_uncancel'));
        }

        return redirect()
            ->route('purchase.manual-adjustments.cancelled.index')
            ->with('success', __('common.messages.uncancelled'));
    }
}
