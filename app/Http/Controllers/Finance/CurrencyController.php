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

namespace App\Http\Controllers\Finance;

use App\Constants\FlashMessage;
use App\Enums\FileAction;
use App\Enums\FileResources;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\Currency\StoreCurrencyRequest;
use App\Http\Requests\Finance\Currency\UpdateCurrencyRequest;
use App\Models\Finance\Currency;
use App\Support\Data\Finance\CurrencyData;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class CurrencyController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  Request  $request  The HTTP request instance.
     * @return View Returns the rendered Blade view.
     */
    public function index(Request $request): View
    {
        $this->allows(FileResources::CURRENCIES);
        $search = $request->input('search');

        $query = Currency::query();

        if (is_string($search) && $search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                    ->orWhere('code', 'like', "%$search%");
            });
        }

        $currencies = $query
            ->latest()
            ->paginate(self::PER_PAGE_GRID_NORMAL);

        return view('finance.currencies.index', compact('currencies', 'search'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return View Returns the rendered Blade view.
     */
    public function create(): View
    {
        $this->allows(FileResources::CURRENCIES, FileAction::ADD);

        return view('finance.currencies.create');
    }

    /**
     * Handle the incoming request to create a new resource.
     *
     * @param  StoreCurrencyRequest  $request  The validated currency being created.
     * @return RedirectResponse Redirects to the specified route.
     */
    public function store(StoreCurrencyRequest $request): RedirectResponse
    {
        $data = CurrencyData::from($request->validated());

        Currency::query()->create([
            'code' => $data->code,
            'name' => $data->name,
            'symbol' => $data->symbol,
            'is_default' => $data->isDefault ?? false,
            'is_active' => $data->isActive ?? false,
        ]);

        return redirect()
            ->route('finance.currencies.index')
            ->with('success', FlashMessage::created(FileResources::CURRENCIES));
    }

    /**
     * Show the form for editing an open resource.
     *
     * @param  Currency  $currency  The currency resource instance.
     * @return View Returns the rendered Blade view.
     */
    public function edit(Currency $currency): View
    {
        $this->allows(FileResources::CURRENCIES, FileAction::UPDATE);

        return view('finance.currencies.edit', compact('currency'));
    }

    /**
     * Update an existing resource by its object.
     *
     * @param  UpdateCurrencyRequest  $request  The validated currency being saved.
     * @param  Currency  $currency  The currency resource instance.
     * @return RedirectResponse Redirects to the specified route.
     */
    public function update(UpdateCurrencyRequest $request, Currency $currency): RedirectResponse
    {
        $data = CurrencyData::from($request->validated());

        $currency->update([
            'code' => $data->code,
            'name' => $data->name,
            'symbol' => $data->symbol,
            'is_default' => $data->isDefault ?? false,
            'is_active' => $data->isActive ?? false,
        ]);

        return redirect()
            ->route('finance.currencies.index')
            ->with('success', FlashMessage::updated(FileResources::CURRENCIES));
    }

    /**
     * Remove a resource by its object.
     *
     * The default currency is never removable, and a currency that the settings
     * row, a bank account or a ledger entry still points at is refused, so no
     * document can end up naming a currency that is no longer selectable.
     *
     * @param  Currency  $currency  The currency resource instance.
     * @return RedirectResponse Redirects to the specified route.
     */
    public function destroy(Currency $currency): RedirectResponse
    {
        $this->allows(FileResources::CURRENCIES, FileAction::REMOVE);

        if ($currency->is_default) {
            throw ValidationException::withMessages([
                'currency' => __('currencies.validation.default_in_use'),
            ]);
        }

        $blocked = $this->currencyUsage($currency);

        if ($blocked !== []) {
            throw ValidationException::withMessages([
                'currency' => __('currencies.validation.in_use', [
                    'references' => implode(', ', $blocked),
                ]),
            ]);
        }

        $currency->delete();

        return redirect()
            ->route('finance.currencies.index')
            ->with('success', FlashMessage::deleted(FileResources::CURRENCIES));
    }

    /**
     * List the records that still reference a currency.
     *
     * The settings row is the only place that names a currency, and it stores
     * the UUID. Nothing else blocks a currency: bank accounts do not name one,
     * and every ledger that records one does so by `currency_id`.
     *
     * Queried through the query builder so soft-deleted rows still count.
     *
     * @param  Currency  $currency  The currency resource instance.
     * @return list<string> Human readable names of the blocking references.
     */
    private function currencyUsage(Currency $currency): array
    {
        $checks = [
            __('currencies.usage.system_settings') => DB::table('settings')->where('currency_id', $currency->id),
        ];

        $blocked = [];
        foreach ($checks as $label => $query) {
            if ($query->exists()) {
                $blocked[] = $label;
            }
        }

        return $blocked;
    }
}
