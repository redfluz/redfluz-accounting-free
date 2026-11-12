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

namespace app\Http\Controllers\Bank\PaymentVoucher;

use App\Enums\FileResources;
use App\Enums\Finance\DocumentStatus;
use App\Http\Controllers\Controller;
use App\Models\Bank\BankLedger;
use App\Models\Bank\CashLedger;
use App\Models\Bank\EWalletLedger;
use App\Models\Bank\PaymentVoucher;
use App\Models\Crm\ApLedger;
use App\Models\Finance\Ledger;
use App\Models\System\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * Posted payment vouchers. A posted payment voucher is an event that happened,
 * so nothing here changes it.
 */
class HistoryController extends Controller
{
    /**
     * Display a listing of posted payment vouchers.
     *
     * @param  Request  $request  The incoming request containing list filters.
     * @return View Returns the rendered Blade view.
     */
    public function index(Request $request): View
    {
        $this->allows(FileResources::PAYMENT_VOUCHERS_HISTORY);

        /** @var string $search */
        $search = trim($request->input('search', ''));
        /** @var string $startDate */
        $startDate = $request->input('start_date') ?? '';
        /** @var string $endDate */
        $endDate = $request->input('end_date') ?? '';

        $vouchers = PaymentVoucher::query()
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

        return view('bank.voucher.history.index', compact('vouchers', 'search', 'startDate', 'endDate'));
    }

    /**
     * Display the specified posted payment voucher.
     *
     * @param  string  $documentNumber  The document number of the payment voucher.
     * @return View Returns the rendered Blade view.
     */
    public function show(string $documentNumber): View
    {
        $this->allows(FileResources::PAYMENT_VOUCHERS_HISTORY);

        $voucher = PaymentVoucher::query()
            ->where('document_number', $documentNumber)
            ->where('status', DocumentStatus::POSTED)
            ->firstOrFail();

        /** @var Collection<int, BankLedger> $bankLedgers */
        $bankLedgers = BankLedger::query()
            ->with('bankAccount.chart')
            ->where('document_number', $documentNumber)
            ->orderBy('id')
            ->get();

        /** @var Collection<int, CashLedger> $cashLedgers */
        $cashLedgers = CashLedger::query()
            ->with('cashAccount.chart')
            ->where('document_number', $documentNumber)
            ->orderBy('id')
            ->get();

        /** @var Collection<int, EWalletLedger> $eWalletLedgers */
        $eWalletLedgers = EWalletLedger::query()
            ->with('eWalletAccount.chart')
            ->where('document_number', $documentNumber)
            ->orderBy('id')
            ->get();

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

        return view('bank.voucher.history.show', compact(
            'voucher',
            'documentNumber',
            'bankLedgers',
            'cashLedgers',
            'eWalletLedgers',
            'apLedgers',
            'glLedgers',
        ));
    }

    /**
     * Print one posted payment voucher.
     *
     * @param  string  $documentNumber  The document number of the payment voucher.
     * @return Response Returns the PDF download response.
     */
    public function pdf(string $documentNumber): Response
    {
        $this->allows(FileResources::PAYMENT_VOUCHERS_HISTORY);

        $voucher = PaymentVoucher::query()
            ->where('document_number', $documentNumber)
            ->where('status', DocumentStatus::POSTED)
            ->firstOrFail();

        return Pdf::loadView('bank.voucher.pdf', [
            'voucher' => $voucher,
            'documentNumber' => $documentNumber,
            'setting' => Setting::query()->first(),
        ])
            ->setPaper('a4')
            ->stream("payment_voucher_$documentNumber.pdf");
    }
}
