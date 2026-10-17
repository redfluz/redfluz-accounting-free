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
use App\Http\Controllers\Controller;
use App\Http\Requests\Bank\CashAccount\StoreCashAccountRequest;
use App\Http\Requests\Bank\CashAccount\UpdateCashAccountRequest;
use App\Models\Bank\CashAccount;
use App\Models\ChartOfAccount\Chart;
use App\Models\Inventory\StockLocation;
use App\Support\Data\Bank\CashAccountData;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Cash accounts: the drawers and petty-cash registers.
 *
 * Physical cash held by the business, separate from bank accounts and e-wallets.
 * Each drawer or petty cash tin is its own row so a cash count can be performed
 * against it and reconciled independently.
 */
class CashAccountController extends Controller
{
    /**
     * Display a listing of the cash accounts.
     *
     * @param  Request  $request  The incoming request containing list filters.
     * @return View Returns the rendered Blade view.
     */
    public function index(Request $request): View
    {
        $this->allows(FileResources::BANKS);

        $search = $request->string('search')->toString();

        $cashAccounts = CashAccount::query()
            ->with('chart')
            ->when($search !== '', fn ($query) => $query->where(
                fn ($subQuery) => $subQuery->where('name', 'like', "%$search%")
                    ->orWhere('account_name', 'like', "%$search%")
            ))
            ->orderBy('name')
            ->paginate(static::PER_PAGE_GRID_NORMAL)
            ->withQueryString();

        return view('bank.cash-accounts.index', [
            'cashAccounts' => $cashAccounts,
            'search' => $search,
        ]);
    }

    /**
     * Show the form for creating a cash account.
     *
     * @return View Returns the rendered Blade view.
     */
    public function create(): View
    {
        $this->allows(FileResources::BANKS, FileAction::ADD);

        return view('bank.cash-accounts.create', [
            'charts' => $this->freeCharts(null),
            'stockLocations' => $this->stockLocations(),
        ]);
    }

    /**
     * Handle the incoming request to create a cash account.
     *
     * @param  StoreCashAccountRequest  $request  The validated account being created.
     * @return RedirectResponse Redirects to the created resource.
     */
    public function store(StoreCashAccountRequest $request): RedirectResponse
    {
        $data = CashAccountData::from($request->validated());
        $data->countryCode = strtoupper($data->countryCode ?? '');
        $data->isActive = $request->boolean('is_active');
        $cashAccount = CashAccount::query()->create($data->toCreateRow());

        return redirect()->route('bank.cash-accounts.show', $cashAccount)
            ->with('success', FlashMessage::created(FileResources::BANKS));
    }

    /**
     * Display the specified resource.
     *
     * @param  CashAccount  $cashAccount  The cash account resource instance.
     * @return View Returns the rendered Blade view.
     */
    public function show(CashAccount $cashAccount): View
    {
        $this->allows(FileResources::BANKS);
        $cashAccount->load('chart');

        return view('bank.cash-accounts.show', compact('cashAccount'));
    }

    /**
     * Show the form for editing a cash account.
     *
     * @param  CashAccount  $cashAccount  The cash account being edited.
     * @return View Returns the rendered Blade view.
     */
    public function edit(CashAccount $cashAccount): View
    {
        $this->allows(FileResources::BANKS, FileAction::UPDATE);

        return view('bank.cash-accounts.edit', [
            'cashAccount' => $cashAccount,
            'charts' => $this->freeCharts($cashAccount),
            'stockLocations' => $this->stockLocations(),
        ]);
    }

    /**
     * Handle the incoming request to update a cash account.
     *
     * @param  UpdateCashAccountRequest  $request  The validated account changes.
     * @param  CashAccount  $cashAccount  The cash account being updated.
     * @return RedirectResponse Redirects to the specified resource.
     */
    public function update(UpdateCashAccountRequest $request, CashAccount $cashAccount): RedirectResponse
    {
        $data = CashAccountData::from($request->validated());
        $data->countryCode = strtoupper($data->countryCode ?? '');
        $data->isActive = $request->boolean('is_active');
        $row = $data->toRow();
        unset($row['id'], $row['created_at'], $row['updated_at'], $row['deleted_at']);
        $cashAccount->update($row);

        return redirect()->route('bank.cash-accounts.show', $cashAccount)
            ->with('success', FlashMessage::updated(FileResources::BANKS));
    }

    /**
     * Remove a resource by its object.
     *
     * @param  CashAccount  $cashAccount  The cash account resource instance.
     * @return RedirectResponse Redirects to the resource list.
     */
    public function destroy(CashAccount $cashAccount): RedirectResponse
    {
        $this->allows(FileResources::BANKS, FileAction::REMOVE);

        $cashAccount->delete();

        return redirect()->route('bank.cash-accounts.index')
            ->with('success', FlashMessage::deleted(FileResources::BANKS));
    }

    /**
     * The unused money charts, plus the account's own chart when editing.
     *
     * @param  CashAccount|null  $cashAccount  The account being edited, if any.
     * @return Collection<int, Chart> The charts available to the form.
     */
    private function freeCharts(?CashAccount $cashAccount): Collection
    {
        /** @var Collection<int, Chart> $charts */
        $charts = Chart::query()->where('isBank', true)
            ->where(function ($query) use ($cashAccount): void {
                /** @var Builder<Chart> $query */
                if ($cashAccount !== null) {
                    $query->whereKey($cashAccount->chart_id)->orWhereNotExists(
                        function ($subQuery): void {
                            /** @var \Illuminate\Database\Query\Builder $subQuery */
                            $subQuery->selectRaw('1')->from('cash_accounts')->whereColumn('cash_accounts.chart_id', 'charts.id');
                        }
                    );
                } else {
                    $query->whereNotExists(
                        function ($subQuery): void {
                            /** @var \Illuminate\Database\Query\Builder $subQuery */
                            $subQuery->selectRaw('1')->from('cash_accounts')->whereColumn('cash_accounts.chart_id', 'charts.id');
                        }
                    );
                }
            })
            ->orderBy('code')->get();

        return $charts;
    }

    /**
     * The stock locations available as cash-keeping sites.
     *
     * @return Collection<int, StockLocation> The active stock locations ordered by name.
     */
    private function stockLocations(): Collection
    {
        return StockLocation::query()->orderBy('name')->get();
    }
}
