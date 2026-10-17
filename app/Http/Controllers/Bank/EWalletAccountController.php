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
use App\Http\Requests\Bank\EWalletAccount\StoreEWalletAccountRequest;
use App\Http\Requests\Bank\EWalletAccount\UpdateEWalletAccountRequest;
use App\Models\Bank\EWalletAccount;
use App\Models\Bank\EWalletProvider;
use App\Models\ChartOfAccount\Chart;
use App\Support\Data\Bank\EWalletAccountData;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * E-wallet accounts: one row per wallet held with a provider.
 *
 * Money in a third-party wallet, separate from bank accounts and physical cash.
 * Each wallet is its own row so movements can be reconciled against the provider
 * statement independently.
 */
class EWalletAccountController extends Controller
{
    /**
     * Display a listing of the e-wallet accounts.
     *
     * @param  Request  $request  The incoming request containing list filters.
     * @return View Returns the rendered Blade view.
     */
    public function index(Request $request): View
    {
        $this->allows(FileResources::BANKS);

        $search = $request->string('search')->toString();

        $eWalletAccounts = EWalletAccount::query()
            ->with(['chart', 'eWalletProvider'])
            ->when($search !== '', fn ($query) => $query->where(
                fn ($subQuery) => $subQuery->where('name', 'like', "%$search%")
                    ->orWhere('account_name', 'like', "%$search%")
                    ->orWhere('account_number', 'like', "%$search%")
            ))
            ->orderBy('name')
            ->paginate(static::PER_PAGE_GRID_NORMAL)
            ->withQueryString();

        return view('bank.e-wallet-accounts.index', [
            'eWalletAccounts' => $eWalletAccounts,
            'search' => $search,
        ]);
    }

    /**
     * Show the form for creating an e-wallet account.
     *
     * @return View Returns the rendered Blade view.
     */
    public function create(): View
    {
        $this->allows(FileResources::BANKS, FileAction::ADD);

        return view('bank.e-wallet-accounts.create', [
            'charts' => $this->freeCharts(null),
            'eWalletProviders' => $this->eWalletProviders(),
        ]);
    }

    /**
     * Handle the incoming request to create an e-wallet account.
     *
     * @param  StoreEWalletAccountRequest  $request  The validated account being created.
     * @return RedirectResponse Redirects to the created resource.
     */
    public function store(StoreEWalletAccountRequest $request): RedirectResponse
    {
        $data = EWalletAccountData::from($request->validated());
        $data->countryCode = strtoupper($data->countryCode ?? '');
        $data->isActive = $request->boolean('is_active');
        $eWalletAccount = EWalletAccount::query()->create($data->toCreateRow());

        return redirect()->route('bank.e-wallet-accounts.show', $eWalletAccount)
            ->with('success', FlashMessage::created(FileResources::BANKS));
    }

    /**
     * Display the specified resource.
     *
     * @param  EWalletAccount  $eWalletAccount  The e-wallet account resource instance.
     * @return View Returns the rendered Blade view.
     */
    public function show(EWalletAccount $eWalletAccount): View
    {
        $this->allows(FileResources::BANKS);
        $eWalletAccount->load(['chart', 'eWalletProvider']);

        return view('bank.e-wallet-accounts.show', compact('eWalletAccount'));
    }

    /**
     * Show the form for editing an e-wallet account.
     *
     * @param  EWalletAccount  $eWalletAccount  The e-wallet account being edited.
     * @return View Returns the rendered Blade view.
     */
    public function edit(EWalletAccount $eWalletAccount): View
    {
        $this->allows(FileResources::BANKS, FileAction::UPDATE);

        return view('bank.e-wallet-accounts.edit', [
            'eWalletAccount' => $eWalletAccount,
            'charts' => $this->freeCharts($eWalletAccount),
            'eWalletProviders' => $this->eWalletProviders(),
        ]);
    }

    /**
     * Handle the incoming request to update an e-wallet account.
     *
     * @param  UpdateEWalletAccountRequest  $request  The validated account changes.
     * @param  EWalletAccount  $eWalletAccount  The e-wallet account being updated.
     * @return RedirectResponse Redirects to the specified resource.
     */
    public function update(UpdateEWalletAccountRequest $request, EWalletAccount $eWalletAccount): RedirectResponse
    {
        $data = EWalletAccountData::from($request->validated());
        $data->countryCode = strtoupper($data->countryCode ?? '');
        $data->isActive = $request->boolean('is_active');
        $row = $data->toRow();
        unset($row['id'], $row['created_at'], $row['updated_at'], $row['deleted_at']);
        $eWalletAccount->update($row);

        return redirect()->route('bank.e-wallet-accounts.show', $eWalletAccount)
            ->with('success', FlashMessage::updated(FileResources::BANKS));
    }

    /**
     * Remove a resource by its object.
     *
     * @param  EWalletAccount  $eWalletAccount  The e-wallet account resource instance.
     * @return RedirectResponse Redirects to the resource list.
     */
    public function destroy(EWalletAccount $eWalletAccount): RedirectResponse
    {
        $this->allows(FileResources::BANKS, FileAction::REMOVE);

        $eWalletAccount->delete();

        return redirect()->route('bank.e-wallet-accounts.index')
            ->with('success', FlashMessage::deleted(FileResources::BANKS));
    }

    /**
     * The active e-wallet providers, for the issuer selector.
     *
     * @return Collection<int, EWalletProvider> The providers ordered by name.
     */
    private function eWalletProviders(): Collection
    {
        return EWalletProvider::query()->active()->orderBy('name')->get();
    }

    /**
     * The unused money charts, plus the account's own chart when editing.
     *
     * @param  EWalletAccount|null  $eWalletAccount  The account being edited, if any.
     * @return Collection<int, Chart> The charts available to the form.
     */
    private function freeCharts(?EWalletAccount $eWalletAccount): Collection
    {
        /** @var Collection<int, Chart> $charts */
        $charts = Chart::query()->where('isBank', true)
            ->where(function ($query) use ($eWalletAccount): void {
                /** @var Builder<Chart> $query */
                if ($eWalletAccount !== null) {
                    $query->whereKey($eWalletAccount->chart_id)->orWhereNotExists(
                        function ($subQuery): void {
                            /** @var \Illuminate\Database\Query\Builder $subQuery */
                            $subQuery->selectRaw('1')->from('e_wallet_accounts')->whereColumn('e_wallet_accounts.chart_id', 'charts.id');
                        }
                    );
                } else {
                    $query->whereNotExists(
                        function ($subQuery): void {
                            /** @var \Illuminate\Database\Query\Builder $subQuery */
                            $subQuery->selectRaw('1')->from('e_wallet_accounts')->whereColumn('e_wallet_accounts.chart_id', 'charts.id');
                        }
                    );
                }
            })
            ->orderBy('code')->get();

        return $charts;
    }
}
