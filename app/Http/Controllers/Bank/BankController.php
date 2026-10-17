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

namespace App\Http\Controllers\Bank;

use App\Constants\FlashMessage;
use App\Enums\FileAction;
use App\Enums\FileResources;
use App\Enums\LedgerStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Bank\Bank\StoreBankRequest;
use App\Http\Requests\Bank\Bank\UpdateBankRequest;
use App\Models\Bank\Bank;
use App\Models\ChartOfAccount\Chart;
use App\Models\Finance\Ledger;
use App\Services\DecimalService;
use App\Support\Data\Bank\BankData;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class BankController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  Request  $request  The HTTP request instance.
     * @return View Returns the rendered Blade view.
     */
    public function index(Request $request): View
    {
        $this->allows(FileResources::BANKS);
        $search = $request->string('search')->toString();
        $banks = Bank::query()
            ->with('chart')
            ->when($search !== '', fn ($query) => $query->where(
                fn ($subQuery) => $subQuery->where('name', 'like', "%$search%")
                    ->orWhere('account_name', 'like', "%$search%")
                    ->orWhere('account_number', 'like', "%$search%")
                    ->orWhere('swift_code', 'like', "%$search%")
            ))
            ->orderBy('name')
            ->paginate(self::PER_PAGE_GRID_NORMAL)
            ->withQueryString();

        return view('bank.banks.index', compact('banks', 'search'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return View Returns the rendered Blade view.
     */
    public function create(): View
    {
        $this->allows(FileResources::BANKS, FileAction::ADD);
        /** @var Collection<int, Chart> $charts */
        $charts = Chart::query()->where('isBank', true)
            ->whereNotExists(function ($query): void {
                /** @var \Illuminate\Database\Query\Builder $query */
                $query->selectRaw('1')->from('banks')->whereColumn('banks.chart_id', 'charts.id');
            })
            ->orderBy('code')->get();

        return view('bank.banks.create', [
            'charts' => $charts,
        ]);
    }

    /**
     * Handle the incoming request to create a new resource.
     *
     * @param  StoreBankRequest  $request  The validated bank being created.
     * @return RedirectResponse Redirects to the created resource.
     */
    public function store(StoreBankRequest $request): RedirectResponse
    {
        $data = BankData::from($request->validated());
        $data->bankCode = filled($data->bankCode) ? strtoupper($data->bankCode) : null;
        $data->swiftCode = filled($data->swiftCode) ? strtoupper($data->swiftCode) : null;
        $data->iban = filled($data->iban) ? strtoupper(str_replace(' ', '', $data->iban)) : null;
        $data->countryCode = strtoupper($data->countryCode ?? '');
        $data->isActive = $request->boolean('is_active');
        $bank = Bank::query()->create($data->toCreateRow());

        return redirect()->route('bank.banks.show', $bank)
            ->with('success', FlashMessage::created(FileResources::BANKS));
    }

    /**
     * Display the specified resource.
     *
     * @param  Request  $request  The HTTP request instance.
     * @param  Bank  $bank  The bank resource instance.
     * @return View Returns the rendered Blade view.
     */
    public function show(Request $request, Bank $bank): View
    {
        $this->allows(FileResources::BANKS);
        $bank->load('chart');

        /** @var array{year?: int} $validated */
        $validated = $request->validate([
            'year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
        ]);
        $selectedYear = $validated['year'] ?? now()->year;
        $yearStart = Carbon::parse($selectedYear.'-01-01')->startOfDay();
        $yearEnd = $yearStart->copy()->endOfYear();
        $chartCode = $bank->chart()->first()?->code;

        $ledgerQuery = Ledger::query()
            ->where('status', LedgerStatus::POSTED)
            ->where(function (Builder $query) use ($bank, $chartCode): void {
                if (! filled($bank->chart_id) && ! filled($chartCode)) {
                    $query->whereRaw('1 = 0');

                    return;
                }

                $query->where('debit_id', $bank->chart_id)
                    ->orWhere('credit_id', $bank->chart_id);

                if (filled($chartCode)) {
                    $query->orWhere('debit_acc', $chartCode)
                        ->orWhere('credit_acc', $chartCode);
                }
            });

        $openingDebit = DecimalService::normalize((string) (clone $ledgerQuery)->whereDate('date', '<', $yearStart)->sum('debit_amount'));
        $openingCredit = DecimalService::normalize((string) (clone $ledgerQuery)->whereDate('date', '<', $yearStart)->sum('credit_amount'));
        $openingBalance = bcsub($openingDebit, $openingCredit, 2);

        /** @var Collection<int, Ledger> $yearLedgers */
        $yearLedgers = (clone $ledgerQuery)
            ->whereBetween('date', [$yearStart, $yearEnd])
            ->oldest('date')
            ->get(['id', 'date', 'document_number', 'debit_amount', 'credit_amount']);
        $ledgersByMonth = $yearLedgers->groupBy(fn (Ledger $ledger): int => $ledger->date->month);
        $closingBalance = $openingBalance;
        $monthlyStatistics = collect(range(1, 12))->map(function (int $month) use ($ledgersByMonth, $selectedYear, &$closingBalance): array {
            /** @var Collection<int, Ledger> $monthLedgers */
            $monthLedgers = $ledgersByMonth->get($month, new Collection);
            $debitSum = $monthLedgers->sum('debit_amount');
            $creditSum = $monthLedgers->sum('credit_amount');
            $debit = DecimalService::normalize(is_numeric($debitSum) ? (string) $debitSum : '0');
            $credit = DecimalService::normalize(is_numeric($creditSum) ? (string) $creditSum : '0');
            $netMovement = bcsub($debit, $credit, 2);
            $closingBalance = bcadd($closingBalance, $netMovement, 2);
            $monthStart = Carbon::parse($selectedYear.'-'.$month.'-01')->startOfMonth();

            return [
                'month' => $monthStart->translatedFormat('F'),
                'start_date' => $monthStart->toDateString(),
                'end_date' => $monthStart->copy()->endOfMonth()->toDateString(),
                'transaction_count' => $monthLedgers->pluck('document_number')->filter()->unique()->count(),
                'debit' => $debit,
                'credit' => $credit,
                'net_movement' => $netMovement,
                'closing_balance' => $closingBalance,
            ];
        });
        $yearDebitSum = $yearLedgers->sum('debit_amount');
        $yearCreditSum = $yearLedgers->sum('credit_amount');
        $yearDebit = DecimalService::normalize(is_numeric($yearDebitSum) ? (string) $yearDebitSum : '0');
        $yearCredit = DecimalService::normalize(is_numeric($yearCreditSum) ? (string) $yearCreditSum : '0');
        $yearTotals = [
            'transaction_count' => $yearLedgers->pluck('document_number')->filter()->unique()->count(),
            'debit' => $yearDebit,
            'credit' => $yearCredit,
            'net_movement' => bcsub($yearDebit, $yearCredit, 2),
            'closing_balance' => $closingBalance,
        ];

        return view('bank.banks.show', compact(
            'bank',
            'selectedYear',
            'openingBalance',
            'monthlyStatistics',
            'yearTotals'
        ));
    }

    /**
     * Show the form for editing an open resource.
     *
     * @param  Bank  $bank  The bank resource instance.
     * @return View Returns the rendered Blade view.
     */
    public function edit(Bank $bank): View
    {
        $this->allows(FileResources::BANKS, FileAction::UPDATE);
        /** @var Collection<int, Chart> $charts */
        $charts = Chart::query()->where('isBank', true)
            ->where(function ($query) use ($bank): void {
                /** @var Builder<Chart> $query */
                $query->whereKey($bank->chart_id)->orWhereNotExists(
                    function ($subQuery): void {
                        /** @var \Illuminate\Database\Query\Builder $subQuery */
                        $subQuery->selectRaw('1')->from('banks')->whereColumn('banks.chart_id', 'charts.id');
                    }
                );
            })
            ->orderBy('code')->get();

        return view('bank.banks.edit', [
            'bank' => $bank,
            'charts' => $charts,
        ]);
    }

    /**
     * Update an existing resource by its object.
     *
     * @param  UpdateBankRequest  $request  The validated bank being saved.
     * @param  Bank  $bank  The bank resource instance.
     * @return RedirectResponse Redirects to the updated resource.
     */
    public function update(UpdateBankRequest $request, Bank $bank): RedirectResponse
    {
        $data = BankData::from($request->validated());
        $data->bankCode = filled($data->bankCode) ? strtoupper($data->bankCode) : null;
        $data->swiftCode = filled($data->swiftCode) ? strtoupper($data->swiftCode) : null;
        $data->iban = filled($data->iban) ? strtoupper(str_replace(' ', '', $data->iban)) : null;
        $data->countryCode = strtoupper($data->countryCode ?? '');
        $data->isActive = $request->boolean('is_active');
        $row = $data->toRow();
        unset($row['id'], $row['created_at'], $row['updated_at'], $row['deleted_at']);
        $bank->update($row);

        return redirect()->route('bank.banks.show', $bank)
            ->with('success', FlashMessage::updated(FileResources::BANKS));
    }

    /**
     * Remove a resource by its object.
     *
     * @param  Bank  $bank  The bank resource instance.
     * @return RedirectResponse Redirects to the resource list.
     */
    public function destroy(Bank $bank): RedirectResponse
    {
        $this->allows(FileResources::BANKS, FileAction::REMOVE);

        $code = Chart::query()->whereKey($bank->chart_id)->value('code');

        $entries = $code === null
            ? 0
            : Ledger::query()
                ->whereNull('deleted_at')
                ->where(function (Builder $query) use ($code): void {
                    $query->where('debit_acc', $code)->orWhere('credit_acc', $code);
                })
                ->count();

        if ($entries > 0) {
            throw ValidationException::withMessages([
                'bank' => __('banks.validation.in_use', ['count' => $entries]),
            ]);
        }

        $bank->delete();

        return redirect()->route('bank.banks.index')
            ->with('success', FlashMessage::deleted(FileResources::BANKS));
    }
}
