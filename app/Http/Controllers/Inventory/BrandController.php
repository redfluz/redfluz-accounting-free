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

namespace App\Http\Controllers\Inventory;

use App\Constants\FlashMessage;
use App\Enums\FileAction;
use App\Enums\FileResources;
use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\Brand\StoreBrandRequest;
use App\Http\Requests\Inventory\Brand\UpdateBrandRequest;
use App\Models\Inventory\Brand;
use App\Support\Data\Inventory\BrandData;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class BrandController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  Request  $request  The HTTP request instance.
     * @return View Returns the rendered Blade view.
     */
    public function index(Request $request): View
    {
        $this->allows(FileResources::BRANDS);
        $search = $request->string('search')->toString();
        $brands = Brand::query()
            ->withCount(['products' => fn (Builder $query) => $query->whereNull('parent_product_id')])
            ->when($search !== '', fn ($query) => $query->where(
                fn ($subQuery) => $subQuery->where('name', 'like', "%$search%")
                    ->orWhere('description', 'like', "%$search%")
            ))
            ->orderBy('name')
            ->paginate(self::PER_PAGE_GRID_NORMAL)
            ->withQueryString();

        return view('inventory.brands.index', compact('brands', 'search'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return View Returns the rendered Blade view.
     */
    public function create(): View
    {
        $this->allows(FileResources::BRANDS, FileAction::ADD);

        return view('inventory.brands.create');
    }

    /**
     * Handle the incoming request to create a new resource.
     *
     * @param  StoreBrandRequest  $request  The validated request instance.
     * @return RedirectResponse Redirects to the created resource.
     */
    public function store(StoreBrandRequest $request): RedirectResponse
    {
        $data = BrandData::from($request->validated());

        $brand = new Brand;
        $brand->fill($data->toCreateRow());
        $brand->save();

        return redirect()->route('inventory.brands.show', $brand)
            ->with('success', FlashMessage::created(FileResources::BRANDS));
    }

    /**
     * Display the specified resource.
     *
     * @param  Brand  $brand  The brand resource instance.
     * @return View Returns the rendered Blade view.
     */
    public function show(Brand $brand): View
    {
        $this->allows(FileResources::BRANDS);
        $products = $brand->products()->whereNull('parent_product_id')
            ->orderBy('name')->paginate(self::PER_PAGE_GRID_NORMAL);

        return view('inventory.brands.show', compact('brand', 'products'));
    }

    /**
     * Show the form for editing an open resource.
     *
     * @param  Brand  $brand  The brand resource instance.
     * @return View Returns the rendered Blade view.
     */
    public function edit(Brand $brand): View
    {
        $this->allows(FileResources::BRANDS, FileAction::UPDATE);

        return view('inventory.brands.edit', compact('brand'));
    }

    /**
     * Update an existing resource by its object.
     *
     * @param  UpdateBrandRequest  $request  The validated request instance.
     * @param  Brand  $brand  The brand resource instance.
     * @return RedirectResponse Redirects to the updated resource.
     */
    public function update(UpdateBrandRequest $request, Brand $brand): RedirectResponse
    {
        $data = BrandData::from($request->validated());

        $brand->update([
            'name' => $data->name,
            'description' => $data->description,
            'is_active' => $data->isActive ?? false,
        ]);

        return redirect()->route('inventory.brands.show', $brand)
            ->with('success', FlashMessage::updated(FileResources::BRANDS));
    }

    /**
     * Remove a resource by its object.
     *
     * @param  Brand  $brand  The brand resource instance.
     * @return RedirectResponse Redirects to the resource list.
     */
    public function destroy(Brand $brand): RedirectResponse
    {
        $this->allows(FileResources::BRANDS, FileAction::REMOVE);
        if ($brand->products()->exists()) {
            throw ValidationException::withMessages([
                'brand' => __('brands.validation.brand_in_use'),
            ]);
        }
        $brand->delete();

        return redirect()->route('inventory.brands.index')
            ->with('success', FlashMessage::deleted(FileResources::BRANDS));
    }
}
