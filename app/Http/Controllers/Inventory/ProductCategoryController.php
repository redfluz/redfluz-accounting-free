<?php

namespace App\Http\Controllers\Inventory;

use App\Constants\FlashMessage;
use App\Enums\FileAction;
use App\Enums\FileResources;
use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\ProductCategory\StoreProductCategoryRequest;
use App\Http\Requests\Inventory\ProductCategory\UpdateProductCategoryRequest;
use App\Models\Inventory\ProductCategory;
use App\Support\Data\Inventory\ProductCategoryData;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ProductCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     * Display a listing of product categories.
     *
     * @param  Request  $request  The HTTP request instance.
     * @return View Returns the rendered Blade view.
     */
    public function index(Request $request): View
    {
        $this->allows(FileResources::PRODUCT_CATEGORIES);
        $search = $request->string('search')->toString();
        $categories = ProductCategory::query()
            ->withCount([
                'types',
                'products' => fn (Builder $query) => $query->whereNull('parent_product_id'),
            ])
            ->when($search !== '', fn ($query) => $query->where(
                fn ($subQuery) => $subQuery->where('code', 'like', "%$search%")
                    ->orWhere('name', 'like', "%$search%")
            ))
            ->orderBy('name')
            ->paginate(self::PER_PAGE_GRID_NORMAL)
            ->withQueryString();

        return view('inventory.product-categories.index', compact('categories', 'search'));
    }

    /**
     * Show the form for creating a new resource.
     * Show the form for creating a product category.
     *
     * @return View Returns the rendered Blade view.
     */
    public function create(): View
    {
        $this->allows(FileResources::PRODUCT_CATEGORIES, FileAction::ADD);

        return view('inventory.product-categories.create');
    }

    /**
     * Handle the incoming request to create a new resource.
     *
     * @param  StoreProductCategoryRequest  $request  The validated request instance.
     * @return RedirectResponse Redirects to the product category listing.
     */
    public function store(StoreProductCategoryRequest $request): RedirectResponse
    {
        $data = ProductCategoryData::from($request->validated());

        ProductCategory::query()->create($data->toCreateRow());

        return redirect()->route('inventory.product-categories.index')
            ->with('success', FlashMessage::created(FileResources::PRODUCT_CATEGORIES));
    }

    /**
     * Show the form for editing an open resource.
     * Show the form for editing a product category.
     *
     * @param  ProductCategory  $productCategory  The product category resource instance.
     * @return View Returns the rendered Blade view.
     */
    public function edit(ProductCategory $productCategory): View
    {
        $this->allows(FileResources::PRODUCT_CATEGORIES, FileAction::UPDATE);

        return view('inventory.product-categories.edit', compact('productCategory'));
    }

    /**
     * Update an existing resource by its object.
     * Validate and update a product category.
     *
     * @param  UpdateProductCategoryRequest  $request  The validated request instance.
     * @param  ProductCategory  $productCategory  The product category resource instance.
     * @return RedirectResponse Redirects to the product category listing.
     */
    public function update(UpdateProductCategoryRequest $request, ProductCategory $productCategory): RedirectResponse
    {
        $data = ProductCategoryData::from($request->validated());

        $productCategory->update($data->toRow());

        return redirect()->route('inventory.product-categories.index')
            ->with('success', FlashMessage::updated(FileResources::PRODUCT_CATEGORIES));
    }

    /**
     * Remove a resource by its object.
     * Remove an unused product category.
     *
     * @param  ProductCategory  $productCategory  The product category resource instance.
     * @return RedirectResponse Redirects to the product category listing.
     */
    public function destroy(ProductCategory $productCategory): RedirectResponse
    {
        $this->allows(FileResources::PRODUCT_CATEGORIES, FileAction::REMOVE);
        if ($productCategory->types()->exists() || $productCategory->products()->exists()) {
            throw ValidationException::withMessages([
                'product_category' => __('product_categories.validation.in_use'),
            ]);
        }

        $productCategory->delete();

        return redirect()->route('inventory.product-categories.index')
            ->with('success', FlashMessage::deleted(FileResources::PRODUCT_CATEGORIES));
    }
}
