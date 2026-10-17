<?php

namespace App\Http\Controllers\Inventory;

use App\Constants\FlashMessage;
use App\Enums\FileAction;
use App\Enums\FileResources;
use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\ProductType\StoreProductTypeRequest;
use App\Http\Requests\Inventory\ProductType\UpdateProductTypeRequest;
use App\Models\Inventory\ProductCategory;
use App\Models\Inventory\ProductType;
use App\Support\Data\Inventory\ProductTypeData;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class ProductTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     * Display a listing of product types.
     *
     * @param  Request  $request  The HTTP request instance.
     * @return View Returns the rendered Blade view.
     */
    public function index(Request $request): View
    {
        $this->allows(FileResources::PRODUCT_TYPES);
        $search = $request->string('search')->toString();
        $types = ProductType::query()
            ->with('category')
            ->withCount(['products' => fn (Builder $query) => $query->whereNull('parent_product_id')])
            ->when($search !== '', fn ($query) => $query->where(
                fn ($subQuery) => $subQuery->where('code', 'like', "%$search%")
                    ->orWhere('name', 'like', "%$search%")
                    ->orWhereHas('category', fn (Builder $categoryQuery) => $categoryQuery->where('name', 'like', "%$search%"))
            ))
            ->orderBy('name')
            ->paginate(self::PER_PAGE_GRID_NORMAL)
            ->withQueryString();

        return view('inventory.product-types.index', compact('types', 'search'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return View Returns the rendered Blade view.
     */
    public function create(): View
    {
        $this->allows(FileResources::PRODUCT_TYPES, FileAction::ADD);
        $categories = ProductCategory::query()->where('is_active', true)->orderBy('name')->get();

        return view('inventory.product-types.create', compact('categories'));
    }

    /**
     * Handle the incoming request to create a new resource.
     * Validates the incoming request data and stores a new record in the database.
     *
     * @param  StoreProductTypeRequest  $request  The validated request instance.
     * @return RedirectResponse Redirects to the specified route.
     */
    public function store(StoreProductTypeRequest $request): RedirectResponse
    {
        $data = ProductTypeData::from($request->validated());

        ProductType::query()->create($data->toCreateRow());

        return redirect()->route('inventory.product-types.index')
            ->with('success', FlashMessage::created(FileResources::PRODUCT_TYPES));
    }

    /**
     * Show the form for editing an open resource.
     * Display the specified resource.
     *
     * @param  ProductType  $productType  The product type resource instance.
     * @return View Returns the rendered Blade view.
     */
    public function edit(ProductType $productType): View
    {
        $this->allows(FileResources::PRODUCT_TYPES, FileAction::UPDATE);
        $categories = ProductCategory::query()
            ->where(fn ($query) => $query->where('is_active', true)->orWhereKey($productType->product_category_id))
            ->orderBy('name')
            ->get();

        return view('inventory.product-types.edit', compact('productType', 'categories'));
    }

    /**
     * Update an existing resource by its object.
     *
     * @param  UpdateProductTypeRequest  $request  The validated request instance.
     * @param  ProductType  $productType  The product type resource instance.
     * @return RedirectResponse Redirects to the specified route.
     *
     * @throws Throwable
     */
    public function update(UpdateProductTypeRequest $request, ProductType $productType): RedirectResponse
    {
        $data = ProductTypeData::from($request->validated());

        DB::transaction(function () use ($data, $productType): void {
            $productType->products()->update([
                'product_category_id' => $data->productCategoryId,
            ]);
            $productType->update([
                'product_category_id' => $data->productCategoryId,
                'code' => $data->code,
                'name' => $data->name,
                'is_active' => $data->isActive ?? false,
            ]);
        });

        return redirect()->route('inventory.product-types.index')
            ->with('success', FlashMessage::updated(FileResources::PRODUCT_TYPES));
    }

    /**
     * Remove a resource by its object.
     *
     * @param  ProductType  $productType  The product type resource instance.
     * @return RedirectResponse Redirects to the specified route.
     */
    public function destroy(ProductType $productType): RedirectResponse
    {
        $this->allows(FileResources::PRODUCT_TYPES, FileAction::REMOVE);
        if ($productType->products()->exists()) {
            throw ValidationException::withMessages([
                'product_type' => __('product_types.validation.in_use'),
            ]);
        }

        $productType->delete();

        return redirect()->route('inventory.product-types.index')
            ->with('success', FlashMessage::deleted(FileResources::PRODUCT_TYPES));
    }
}
