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

namespace App\Http\Controllers\Bank\PaymentVoucher;

use App\Enums\FileResources;
use App\Enums\Finance\DocumentStatus;
use App\Http\Controllers\Controller;
use App\Models\Bank\PaymentVoucher;
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
 * Cancelled payment vouchers, and the one thing that can be done to them.
 */
class CancelledController extends Controller
{
    /**
     * Display a listing of cancelled payment vouchers.
     *
     * @param  Request  $request  The incoming request containing list filters.
     * @return View Returns the rendered Blade view.
     */
    public function index(Request $request): View
    {
        $this->allows(FileResources::PAYMENT_VOUCHERS_CANCELLED);

        /** @var string $search */
        $search = $request->input('search', '');
        $search = trim($search);
        /** @var string $startDate */
        $startDate = $request->input('start_date') ?? '';
        /** @var string $endDate */
        $endDate = $request->input('end_date') ?? '';

        $vouchers = PaymentVoucher::query()
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

        return view('bank.voucher.cancelled.index', compact('vouchers', 'search', 'startDate', 'endDate'));
    }

    /**
     * Display the specified cancelled payment voucher.
     *
     * @param  string  $documentNumber  The document number of the payment voucher.
     * @return View Returns the rendered Blade view.
     */
    public function show(string $documentNumber): View
    {
        $this->allows(FileResources::PAYMENT_VOUCHERS_CANCELLED);

        $voucher = PaymentVoucher::query()
            ->where('document_number', $documentNumber)
            ->where('status', DocumentStatus::CANCELLED)
            ->firstOrFail();

        return view('bank.voucher.cancelled.show', compact('voucher', 'documentNumber'));
    }

    /**
     * Print one cancelled payment voucher.
     *
     * @param  string  $documentNumber  The document number of the payment voucher.
     * @return Response Returns the PDF download response.
     */
    public function pdf(string $documentNumber): Response
    {
        $this->allows(FileResources::PAYMENT_VOUCHERS_CANCELLED);

        $voucher = PaymentVoucher::query()
            ->where('document_number', $documentNumber)
            ->where('status', DocumentStatus::CANCELLED)
            ->firstOrFail();

        return Pdf::loadView('bank.voucher.pdf', [
            'voucher' => $voucher,
            'documentNumber' => $documentNumber,
            'setting' => Setting::query()->first(),
        ])
            ->setPaper('a4')
            ->stream("payment_voucher_$documentNumber.pdf");
    }

    /**
     * Restore a cancelled payment voucher, putting it back in the pending list.
     *
     * @param  string  $documentNumber  The document number of the payment voucher.
     * @return RedirectResponse Redirect response to the pending payment voucher list.
     *
     * @throws Throwable
     */
    public function uncancel(string $documentNumber): RedirectResponse
    {
        $this->allows(FileResources::PAYMENT_VOUCHERS_CANCELLED);

        DB::transaction(function () use ($documentNumber): void {
            $voucher = PaymentVoucher::query()
                ->where('document_number', $documentNumber)
                ->where('status', DocumentStatus::CANCELLED)
                ->lockForUpdate()
                ->first();

            abort_if($voucher === null, 404);

            $voucher->update(['status' => DocumentStatus::PENDING]);

            $this->attributeUndone($voucher);
        });

        return redirect()
            ->route('bank.payment-vouchers.index')
            ->with('success', __('common.messages.uncancelled'));
    }
}
