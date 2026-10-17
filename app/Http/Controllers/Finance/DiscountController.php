<?php

namespace App\Http\Controllers\Finance;

use App\Enums\DiscountType;
use App\Enums\FileAction;
use App\Enums\FileResources;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\Discount\StoreDiscountRequest;
use App\Http\Requests\Finance\Discount\UpdateDiscountRequest;
use App\Models\Finance\Discount;
use App\Support\Data\Finance\DiscountData;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

class DiscountController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return View Returns the rendered Blade view.
     */
    public function index(): View
    {
        $this->allows(FileResources::DISCOUNTS);
        /** @var Collection<int, Discount> $discounts */
        $discounts = Discount::query()->orderBy('code')->get();

        return view('finance.discounts.index', compact('discounts'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return View Returns the rendered Blade view.
     */
    public function create(): View
    {
        $this->allows(FileResources::DISCOUNTS, FileAction::ADD);
        $discount = null;
        $types = DiscountType::cases();

        return view('finance.discounts.create', compact('discount', 'types'));
    }

    /**
     * Handle the incoming request to create a new resource.
     *
     * @param  StoreDiscountRequest  $request  The validated discount being created.
     * @return RedirectResponse Redirects to the discount listing.
     */
    public function store(StoreDiscountRequest $request): RedirectResponse
    {
        $data = DiscountData::from($request->validated());

        if ($data->type === DiscountType::PERCENT && (float) $data->value > 100) {
            throw ValidationException::withMessages([
                'value' => __('validation.max.numeric', ['attribute' => __('discounts.form.value'), 'max' => 100]),
            ]);
        }

        $discount = new Discount;
        $discount->fill([
            'code' => strtoupper((string) $data->code),
            'name' => $data->name,
            'type' => $data->type,
            'value' => $data->value,
            'starts_at' => $data->startsAt?->format('Y-m-d'),
            'ends_at' => $data->endsAt?->format('Y-m-d'),
            'is_active' => $data->isActive ?? false,
        ])->save();

        return redirect()->route('finance.discounts.index')->with('success', __('discounts.messages.created'));
    }

    /**
     * Show the form for editing an open resource.
     *
     * @param  Discount  $discount  The discount resource instance.
     * @return View Returns the rendered Blade view.
     */
    public function edit(Discount $discount): View
    {
        $this->allows(FileResources::DISCOUNTS, FileAction::UPDATE);
        $types = DiscountType::cases();

        return view('finance.discounts.edit', compact('discount', 'types'));
    }

    /**
     * Update an existing resource by its object.
     *
     * @param  UpdateDiscountRequest  $request  The validated discount being saved.
     * @param  Discount  $discount  The discount resource instance.
     * @return RedirectResponse Redirects to the discount listing.
     */
    public function update(UpdateDiscountRequest $request, Discount $discount): RedirectResponse
    {
        $data = DiscountData::from($request->validated());

        if ($data->type === DiscountType::PERCENT && (float) $data->value > 100) {
            throw ValidationException::withMessages([
                'value' => __('validation.max.numeric', ['attribute' => __('discounts.form.value'), 'max' => 100]),
            ]);
        }

        $discount->code = strtoupper((string) $data->code);
        $discount->name = $data->name ?? '';
        $discount->type = $data->type ?? DiscountType::AMOUNT;
        $discount->value = number_format((float) ($data->value ?? '0'), 4, '.', '');
        $discount->starts_at = $data->startsAt;
        $discount->ends_at = $data->endsAt;
        $discount->is_active = $data->isActive ?? false;
        $discount->save();

        return redirect()->route('finance.discounts.index')->with('success', __('discounts.messages.updated'));
    }

    /**
     * Remove a resource by its object.
     *
     * @param  Discount  $discount  The discount resource instance.
     * @return RedirectResponse Redirects to the discount listing.
     */
    public function destroy(Discount $discount): RedirectResponse
    {
        $this->allows(FileResources::DISCOUNTS, FileAction::REMOVE);
        $discount->delete();

        return redirect()->route('finance.discounts.index')->with('success', __('discounts.messages.deleted'));
    }
}
