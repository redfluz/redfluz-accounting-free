<?php

namespace App\Http\Controllers\Finance;

use App\Enums\FileAction;
use App\Enums\FileResources;
use App\Enums\TaxMode;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\Tax\StoreTaxRequest;
use App\Http\Requests\Finance\Tax\UpdateTaxRequest;
use App\Models\Finance\Tax;
use App\Support\Data\Finance\TaxData;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;

class TaxController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return View Returns the rendered Blade view.
     */
    public function index(): View
    {
        $this->allows(FileResources::TAXES);
        /** @var Collection<int, Tax> $taxes */
        $taxes = Tax::query()->orderBy('code')->get();

        return view('finance.taxes.index', compact('taxes'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return View Returns the rendered Blade view.
     */
    public function create(): View
    {
        $this->allows(FileResources::TAXES, FileAction::ADD);
        $tax = null;
        $modes = TaxMode::cases();

        return view('finance.taxes.create', compact('tax', 'modes'));
    }

    /**
     * Handle the incoming request to create a new resource.
     *
     * @param  StoreTaxRequest  $request  The validated tax being created.
     * @return RedirectResponse Redirects to the tax listing.
     */
    public function store(StoreTaxRequest $request): RedirectResponse
    {
        $data = TaxData::from($request->validated());

        $tax = new Tax;
        $tax->fill([
            'code' => strtoupper((string) $data->code),
            'name' => $data->name,
            'rate' => $data->rate,
            'default_mode' => $data->defaultMode,
            'is_active' => $data->isActive ?? false,
        ])->save();

        return redirect()->route('finance.taxes.index')->with('success', __('taxes.messages.created'));
    }

    /**
     * Show the form for editing an open resource.
     *
     * @param  Tax  $tax  The tax resource instance.
     * @return View Returns the rendered Blade view.
     */
    public function edit(Tax $tax): View
    {
        $this->allows(FileResources::TAXES, FileAction::UPDATE);
        $modes = TaxMode::cases();

        return view('finance.taxes.edit', compact('tax', 'modes'));
    }

    /**
     * Update an existing resource by its object.
     *
     * @param  UpdateTaxRequest  $request  The validated tax being saved.
     * @param  Tax  $tax  The tax resource instance.
     * @return RedirectResponse Redirects to the tax listing.
     */
    public function update(UpdateTaxRequest $request, Tax $tax): RedirectResponse
    {
        $data = TaxData::from($request->validated());

        $tax->code = strtoupper((string) $data->code);
        $tax->name = $data->name ?? '';
        $tax->rate = number_format((float) ($data->rate ?? '0'), 4, '.', '');
        $tax->default_mode = $data->defaultMode ?? TaxMode::INCLUSIVE;
        $tax->is_active = $data->isActive ?? false;
        $tax->save();

        return redirect()->route('finance.taxes.index')->with('success', __('taxes.messages.updated'));
    }

    /**
     * Remove a resource by its object.
     *
     * @param  Tax  $tax  The tax resource instance.
     * @return RedirectResponse Redirects to the tax listing.
     */
    public function destroy(Tax $tax): RedirectResponse
    {
        $this->allows(FileResources::TAXES, FileAction::REMOVE);
        $tax->delete();

        return redirect()->route('finance.taxes.index')->with('success', __('taxes.messages.deleted'));
    }
}
