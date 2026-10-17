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
use App\Http\Requests\Bank\EWalletProvider\StoreEWalletProviderRequest;
use App\Http\Requests\Bank\EWalletProvider\UpdateEWalletProviderRequest;
use App\Models\Bank\EWalletProvider;
use App\Models\ChartOfAccount\Chart;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class EWalletProviderController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  Request  $request  The HTTP request instance.
     * @return View Returns the rendered Blade view.
     */
    public function index(Request $request): View
    {
        $this->allows(FileResources::E_WALLET_PROVIDERS);
        $search = $request->string('search')->toString();
        $providers = EWalletProvider::query()
            ->with('chart')
            ->withCount('accounts')
            ->when($search !== '', fn ($query) => $query->where(
                fn ($subQuery) => $subQuery->where('name', 'like', "%$search%")
                    ->orWhere('country', 'like', "%$search%")
                    ->orWhere('identifier_label', 'like', "%$search%")
            ))
            ->orderBy('name')
            ->paginate(self::PER_PAGE_GRID_NORMAL)
            ->withQueryString();

        return view('bank.e-wallet-providers.index', compact('providers', 'search'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return View Returns the rendered Blade view.
     */
    public function create(): View
    {
        $this->allows(FileResources::E_WALLET_PROVIDERS, FileAction::ADD);

        return view('bank.e-wallet-providers.create', [
            'charts' => $this->charts(),
        ]);
    }

    /**
     * The charts a provider may be bound to.
     *
     * Every chart that has not been deleted may carry a provider's balance,
     * because the binding only says where the wallets issued by this provider
     * are reported on the ledger.
     *
     * @return Collection<int, Chart>
     */
    private function charts(): Collection
    {
        return Chart::query()->orderBy('code')->orderBy('name')->get();
    }

    /**
     * Handle the incoming request to create a new resource.
     *
     * @param  StoreEWalletProviderRequest  $request  The validated request instance.
     * @return RedirectResponse Redirects to the created resource.
     */
    public function store(StoreEWalletProviderRequest $request): RedirectResponse
    {
        $row = $request->validated();
        $row['is_active'] = $request->boolean('is_active');

        $provider = EWalletProvider::query()->create($row);

        return redirect()->route('bank.e-wallet-providers.show', $provider)
            ->with('success', FlashMessage::created(FileResources::E_WALLET_PROVIDERS));
    }

    /**
     * Display the specified resource.
     *
     * @param  EWalletProvider  $provider  The e-wallet provider resource instance.
     * @return View Returns the rendered Blade view.
     */
    public function show(EWalletProvider $provider): View
    {
        $this->allows(FileResources::E_WALLET_PROVIDERS);
        $provider->load('chart');
        $accounts = $provider->accounts()->with('chart')
            ->orderBy('name')->paginate(self::PER_PAGE_GRID_NORMAL);

        return view('bank.e-wallet-providers.show', compact('provider', 'accounts'));
    }

    /**
     * Show the form for editing an open resource.
     *
     * @param  EWalletProvider  $provider  The e-wallet provider resource instance.
     * @return View Returns the rendered Blade view.
     */
    public function edit(EWalletProvider $provider): View
    {
        $this->allows(FileResources::E_WALLET_PROVIDERS, FileAction::UPDATE);

        return view('bank.e-wallet-providers.edit', [
            'provider' => $provider,
            'charts' => $this->charts(),
        ]);
    }

    /**
     * Update an existing resource by its object.
     *
     * @param  UpdateEWalletProviderRequest  $request  The validated request instance.
     * @param  EWalletProvider  $provider  The e-wallet provider resource instance.
     * @return RedirectResponse Redirects to the updated resource.
     */
    public function update(UpdateEWalletProviderRequest $request, EWalletProvider $provider): RedirectResponse
    {
        $row = $request->validated();
        unset($row['id']);
        $row['is_active'] = $request->boolean('is_active');

        $provider->update($row);

        return redirect()->route('bank.e-wallet-providers.show', $provider)
            ->with('success', FlashMessage::updated(FileResources::E_WALLET_PROVIDERS));
    }

    /**
     * Remove a resource by its object.
     *
     * @param  EWalletProvider  $provider  The e-wallet provider resource instance.
     * @return RedirectResponse Redirects to the resource list.
     */
    public function destroy(EWalletProvider $provider): RedirectResponse
    {
        $this->allows(FileResources::E_WALLET_PROVIDERS, FileAction::REMOVE);
        if ($provider->accounts()->exists()) {
            throw ValidationException::withMessages([
                'provider' => __('e_wallet_providers.validation.provider_in_use'),
            ]);
        }
        $provider->delete();

        return redirect()->route('bank.e-wallet-providers.index')
            ->with('success', FlashMessage::deleted(FileResources::E_WALLET_PROVIDERS));
    }
}
