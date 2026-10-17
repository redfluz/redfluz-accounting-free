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
use App\Enums\AcquisitionType;
use App\Enums\FileAction;
use App\Enums\FileResources;
use App\Enums\ProductMovementDirection;
use App\Enums\ProductMovementSource;
use App\Enums\ProductMovementStatus;
use App\Enums\ProductStockBucket;
use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\Product\StoreProductRequest;
use App\Http\Requests\Inventory\Product\UpdateProductRequest;
use App\Models\Crm\Company;
use App\Models\Finance\Discount;
use App\Models\Finance\Tax;
use App\Models\Inventory\Brand;
use App\Models\Inventory\Product;
use App\Models\Inventory\ProductAttachment;
use App\Models\Inventory\ProductCategory;
use App\Models\Inventory\ProductLedger;
use App\Models\Inventory\ProductType;
use App\Models\Inventory\StockLocation;
use App\Services\DecimalService;
use App\Support\Data\Inventory\ProductData;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  Request  $request  The HTTP request instance.
     * @return View Returns the rendered Blade view.
     */
    public function index(Request $request): View
    {
        $this->allows(FileResources::PRODUCTS);

        $search = $request->input('search');
        $query = Product::query()
            ->whereNull('parent_product_id')
            ->with(['brand', 'category', 'type'])
            ->withCount('variants');

        if (is_string($search) && $search !== '') {
            $query->where(function ($subQuery) use ($search): void {
                $subQuery->where('name', 'like', "%$search%")
                    ->orWhere('sku', 'like', "%$search%")
                    ->orWhere('barcode', 'like', "%$search%")
                    ->orWhere('mpn', 'like', "%$search%")
                    ->orWhere('description', 'like', "%$search%")
                    ->orWhereHas('brand', fn ($brandQuery) => $brandQuery->where('name', 'like', "%$search%"))
                    ->orWhereHas('category', fn ($categoryQuery) => $categoryQuery->where('name', 'like', "%$search%"))
                    ->orWhereHas('type', fn ($typeQuery) => $typeQuery->where('name', 'like', "%$search%"))
                    ->orWhereHas('variants', function ($variantQuery) use ($search): void {
                        $variantQuery->where('variant_name', 'like', "%$search%")
                            ->orWhere('sku', 'like', "%$search%")
                            ->orWhere('variant_details', 'like', "%$search%");
                    });
            });
        }

        $products = $query
            ->latest()
            ->paginate(self::PER_PAGE_GRID_NORMAL)
            ->withQueryString();

        return view('inventory.products.index', compact('products', 'search'));
    }

    /**
     * Display the specified resource.
     * Display one product and its stock movement history from purchase sources.
     *
     * @param  Product  $product  The product resource instance.
     * @return View Returns the rendered Blade view.
     */
    /**
     * Stream a stored product attachment to the browser.
     *
     * Product attachments used to be linked straight at storage/{file_path},
     * which the public disk symlink serves to anyone who knows the path, with no
     * login and no permission check. They now go through this authenticated,
     * permission-checked route, the same way purchase order attachments already
     * do.
     *
     * @param  Product  $product  The product the attachment belongs to.
     * @param  ProductAttachment  $attachment  The attachment resource instance.
     * @return StreamedResponse Returns the requested file download.
     */
    public function downloadAttachment(Product $product, ProductAttachment $attachment): StreamedResponse
    {
        $this->allows(FileResources::PRODUCTS);

        abort_unless($attachment->product_id === $product->id, 404);
        abort_unless($this->documentExists($attachment->file_path), 404);

        return $this->downloadDocument($attachment->file_path, $attachment->original_name);
    }

    public function show(Product $product): View
    {
        $this->allows(FileResources::PRODUCTS);
        $product->load(['brand', 'category', 'type', 'variants', 'attachments']);
        $productIds = [$product->id, ...$product->variants->modelKeys()];

        $stockLocation = StockLocation::query()
            ->where('is_active', true)
            ->first();

        $locationId = $stockLocation?->id;
        $stockByLocation = $this->stockByLocation($productIds);

        $summary = $locationId !== null
            ? ProductLedger::query()
                ->whereIn('product_id', $productIds)
                ->where('stock_location_id', $locationId)
                ->where('stock_bucket', ProductStockBucket::SALEABLE->value)
                ->selectRaw(
                    'COALESCE(SUM(CASE WHEN status = ? AND direction = ? THEN quantity ELSE 0 END), 0) as received_quantity, '
                    .'COALESCE(SUM(CASE WHEN status = ? AND direction = ? THEN quantity ELSE 0 END), 0) as issued_quantity, '
                    .'COALESCE(SUM(CASE WHEN status = ? AND direction = ? THEN quantity ELSE 0 END), 0) as pending_in_quantity, '
                    .'COALESCE(SUM(CASE WHEN status = ? AND direction = ? THEN quantity ELSE 0 END), 0) as pending_out_quantity',
                    [
                        ProductMovementStatus::ACTIVE->value,
                        ProductMovementDirection::IN->value,
                        ProductMovementStatus::ACTIVE->value,
                        ProductMovementDirection::OUT->value,
                        ProductMovementStatus::PENDING->value,
                        ProductMovementDirection::IN->value,
                        ProductMovementStatus::PENDING->value,
                        ProductMovementDirection::OUT->value,
                    ]
                )
                ->first()
            : null;

        $receivedRaw = $summary?->getAttribute('received_quantity') ?? 0;
        $issuedRaw = $summary?->getAttribute('issued_quantity') ?? 0;
        $receivedStr = is_numeric($receivedRaw) ? (string) $receivedRaw : '0';
        $issuedStr = is_numeric($issuedRaw) ? (string) $issuedRaw : '0';

        $currentBalance = bcsub(
            DecimalService::normalize($receivedStr),
            DecimalService::normalize($issuedStr),
            4
        );

        // Movement totals (in, out, resulting balance) per variant for the variants table
        /** @var array<string, array{in: numeric-string, out: numeric-string, balance: numeric-string}> $variantStock */
        $variantStock = [];
        if ($locationId !== null) {
            $stockData = ProductLedger::query()
                ->whereIn('product_id', $productIds)
                ->where('stock_location_id', $locationId)
                ->where('stock_bucket', ProductStockBucket::SALEABLE->value)
                ->where('status', ProductMovementStatus::ACTIVE->value)
                ->select('product_id')
                ->selectRaw(
                    'COALESCE(SUM(CASE WHEN direction = ? THEN quantity ELSE 0 END), 0) as total_in, '
                    .'COALESCE(SUM(CASE WHEN direction = ? THEN quantity ELSE 0 END), 0) as total_out',
                    [
                        ProductMovementDirection::IN->value,
                        ProductMovementDirection::OUT->value,
                    ]
                )
                ->groupBy('product_id')
                ->get();

            foreach ($stockData as $row) {
                $balance = bcsub(
                    DecimalService::normalize((string) $row->total_in),
                    DecimalService::normalize((string) $row->total_out),
                    4
                );
                $variantStock[$row->product_id] = [
                    'in' => DecimalService::normalize((string) $row->total_in),
                    'out' => DecimalService::normalize((string) $row->total_out),
                    'balance' => $balance,
                ];
            }
        }

        // Variants without any movement still need a zeroed row in the table.
        foreach ($productIds as $productId) {
            $variantStock[$productId] ??= [
                'in' => '0',
                'out' => '0',
                'balance' => '0',
            ];
        }

        // Footer totals for the whole family: the parent product plus every
        // variant, which is the figure the summary cards and the movement
        // running balance are built from.
        $variantTotals = ['in' => '0', 'out' => '0', 'balance' => '0'];
        foreach ($productIds as $productId) {
            $row = $variantStock[$productId];

            foreach (array_keys($variantTotals) as $key) {
                $variantTotals[$key] = bcadd($variantTotals[$key], $row[$key], 4);
            }
        }

        $movements = $locationId !== null
            ? ProductLedger::query()
                ->with('product')
                ->whereIn('product_id', $productIds)
                ->where('stock_location_id', $locationId)
                ->whereIn('source_type', [
                    ProductMovementSource::GOODS_RECEIPT->value,
                    ProductMovementSource::PURCHASE_RETURN->value,
                    ProductMovementSource::SALES_INVOICE->value,
                    ProductMovementSource::SALES_RETURN->value,
                    ProductMovementSource::DELIVERY_ORDER->value,
                ])
                ->select('product_ledgers.*')
                // One running balance for the whole family at this location,
                // parent product and every variant together, so the column adds
                // up to the stock figure shown in the summary cards.
                //
                // Chronology comes from created_at, the posting timestamp, not
                // from date: date is a document date that may be back dated and
                // is not stored in one shape everywhere, so ordering by it puts
                // same day movements in the wrong order. The window runs oldest
                // to newest, which is the reverse of the listing below.
                ->selectRaw(
                    'SUM(CASE '
                    .'WHEN status = ? AND direction = ? THEN quantity '
                    .'WHEN status = ? AND direction = ? THEN -quantity '
                    .'ELSE 0 END) OVER ('
                    .'PARTITION BY stock_location_id '
                    .'ORDER BY created_at ASC, id ASC '
                    .'ROWS BETWEEN UNBOUNDED PRECEDING AND CURRENT ROW'
                    .') as running_balance',
                    [
                        ProductMovementStatus::ACTIVE->value,
                        ProductMovementDirection::IN->value,
                        ProductMovementStatus::ACTIVE->value,
                        ProductMovementDirection::OUT->value,
                    ]
                )
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->paginate(self::PER_PAGE_GRID_STANDARD)
            : new LengthAwarePaginator([], 0, self::PER_PAGE_GRID_NORMAL);

        if ($movements->total() > 0) {
            $movements->getCollection()->each(function (ProductLedger $movement): void {
                $documentUrl = match ($movement->source_type) {
                    ProductMovementSource::GOODS_RECEIPT => route(
                        'purchase.goods-receipts.show',
                        $movement->document_number
                    ),
                    ProductMovementSource::PURCHASE_RETURN => route('purchase.returns.show', $movement->document_number),
                    ProductMovementSource::SALES_ORDER => route('sales.sales-orders.show', $movement->document_number),
                    ProductMovementSource::DELIVERY_ORDER => route('sales.delivery-orders.show', $movement->document_number),
                    ProductMovementSource::SALES_INVOICE => route('sales.invoices.show', $movement->document_number),
                    ProductMovementSource::SALES_RETURN => route('sales.adjustments.show', $movement->document_number),
                    // Stock transfers and stock takes have no detail screen in this edition.
                    ProductMovementSource::STOCK_TRANSFER => null,
                    ProductMovementSource::STOCK_TAKE => null,
                };

                $movement->setAttribute('document_url', $documentUrl);
            });
        }

        return view('inventory.products.show', compact(
            'product',
            'summary',
            'currentBalance',
            'stockByLocation',
            'movements',
            'variantStock',
            'variantTotals'
        ));
    }

    /**
     * Build the per-location stock summary shown on the product screen.
     *
     * This edition shares a single warehouse, so the collection normally holds
     * one row, but it is aggregated per location so every active location is
     * reported if more are ever seeded.
     *
     * @param  array<int, string>  $productIds  The product and its variant identifiers.
     * @return EloquentCollection<int, StockLocation> Locations carrying the computed quantities.
     */
    private function stockByLocation(array $productIds): EloquentCollection
    {
        return StockLocation::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(function (StockLocation $location) use ($productIds): StockLocation {
                $totals = ProductLedger::query()
                    ->whereIn('product_id', $productIds)
                    ->where('stock_location_id', $location->id)
                    ->where('stock_bucket', ProductStockBucket::SALEABLE->value)
                    ->selectRaw(
                        'COALESCE(SUM(CASE WHEN status = ? AND direction = ? THEN quantity ELSE 0 END), 0) as active_in_quantity, '
                        .'COALESCE(SUM(CASE WHEN status = ? AND direction = ? THEN quantity ELSE 0 END), 0) as active_out_quantity, '
                        .'COALESCE(SUM(CASE WHEN status = ? AND direction = ? THEN quantity ELSE 0 END), 0) as pending_in_quantity, '
                        .'COALESCE(SUM(CASE WHEN status = ? AND direction = ? THEN quantity ELSE 0 END), 0) as pending_out_quantity',
                        [
                            ProductMovementStatus::ACTIVE->value,
                            ProductMovementDirection::IN->value,
                            ProductMovementStatus::ACTIVE->value,
                            ProductMovementDirection::OUT->value,
                            ProductMovementStatus::PENDING->value,
                            ProductMovementDirection::IN->value,
                            ProductMovementStatus::PENDING->value,
                            ProductMovementDirection::OUT->value,
                        ]
                    )
                    ->first();

                $activeInRaw = $totals?->getAttribute('active_in_quantity') ?? 0;
                $activeOutRaw = $totals?->getAttribute('active_out_quantity') ?? 0;
                $pendingInRaw = $totals?->getAttribute('pending_in_quantity') ?? 0;
                $pendingOutRaw = $totals?->getAttribute('pending_out_quantity') ?? 0;
                $activeInStr = is_numeric($activeInRaw) ? (string) $activeInRaw : '0';
                $activeOutStr = is_numeric($activeOutRaw) ? (string) $activeOutRaw : '0';
                $pendingInStr = is_numeric($pendingInRaw) ? (string) $pendingInRaw : '0';
                $pendingOutStr = is_numeric($pendingOutRaw) ? (string) $pendingOutRaw : '0';

                $location->setAttribute(
                    'available_quantity',
                    bcsub(
                        DecimalService::normalize($activeInStr),
                        DecimalService::normalize($activeOutStr),
                        4
                    )
                );
                $location->setAttribute('pending_in_quantity', $pendingInStr);
                $location->setAttribute('pending_out_quantity', $pendingOutStr);

                return $location;
            });
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return View Returns the rendered Blade view.
     */
    public function create(): View
    {
        $this->allows(FileResources::PRODUCTS, FileAction::ADD);

        $taxes = Tax::query()->where('is_active', true)->orderBy('code')->get();
        $discounts = Discount::query()->where('is_active', true)->orderBy('code')->get();
        $brands = Brand::query()->where('is_active', true)->orderBy('name')->get();
        $suppliers = Company::query()->where('is_creditor', true)->orderBy('name')->get(['id', 'name']);
        $categories = ProductCategory::query()
            ->where('is_active', true)
            ->with(['types' => function ($query): void {
                /** @var Builder<ProductCategory> $query */
                $query->where('is_active', true);
            }])
            ->orderBy('name')
            ->get();

        $defaultWarrantyMonths = 12;

        return view('inventory.products.create', compact('taxes', 'discounts', 'brands', 'suppliers', 'categories', 'defaultWarrantyMonths'));
    }

    /**
     * Handle the incoming request to create a new resource.
     * Store a newly created product or service.
     *
     * @param  StoreProductRequest  $request  The validated request instance.
     * @return RedirectResponse Redirects to the specified route.
     *
     * @throws Throwable
     */
    public function store(StoreProductRequest $request): RedirectResponse
    {
        $data = ProductData::from($request->validated());

        $variants = $request->validated('variants', []);
        if (! is_array($variants)) {
            throw ValidationException::withMessages([
                'variants' => __('validation.array', ['attribute' => __('products.tabs.variants')]),
            ]);
        }
        $variantRows = collect($variants)
            ->filter(fn (mixed $variant): bool => is_array($variant))
            ->filter(
                fn (array $variant): bool => filled($variant['variant_name'] ?? null)
                    || filled($variant['sku'] ?? null)
            );
        if ($variantRows->contains(
            function (array $variant) use ($data): bool {
                $variantSku = $variant['sku'] ?? '';

                return strcasecmp(is_string($variantSku) ? $variantSku : '', (string) $data->sku) === 0;
            }
        )) {
            throw ValidationException::withMessages([
                'variants' => __('products.validation.variant_sku_matches_product'),
            ]);
        }
        foreach ($variantRows as $index => $variant) {
            if (! ($variant['inherits_selling_price'] ?? false) && ! isset($variant['selling_price'])) {
                throw ValidationException::withMessages([
                    "variants.$index.selling_price" => __('products.validation.variant_selling_price_required'),
                ]);
            }
        }
        $isService = (bool) $data->isService;
        $data->warrantyMonths = $data->warrantyMonths === -1 ? null : $data->warrantyMonths;
        $data->isService = $isService;
        $data->isProduct = ! $isService;
        $data->defaultAcquisitionType = $isService
            ? AcquisitionType::EXPENSE
            : $data->defaultAcquisitionType;
        if ($data->defaultAcquisitionType !== AcquisitionType::STOCK) {
            $data->reorderLevel = '0';
            $data->reorderQuantity = '0';
        }
        $uploadedImages = $request->file('images', []);
        if (! is_array($uploadedImages)) {
            $uploadedImages = [];
        }

        $storedPaths = [];
        try {
            DB::transaction(function () use ($data, $request, &$storedPaths, $uploadedImages, $variantRows): void {
                $product = new Product;
                $product->fill($data->toCreateRow());
                $product->save();

                $now = now();
                $rows = $variantRows->map(function (array $variant) use ($now, $product): array {
                    $inheritsSellingPrice = (bool) ($variant['inherits_selling_price'] ?? false);

                    return [
                        'id' => (string) Str::uuid7(),
                        'parent_product_id' => $product->id,
                        'brand_id' => $product->brand_id,
                        'product_category_id' => $product->product_category_id,
                        'product_type_id' => $product->product_type_id,
                        'name' => $product->name.' - '.(is_string($variant['variant_name'] ?? null) ? $variant['variant_name'] : ''),
                        'sku' => $variant['sku'],
                        'barcode' => $product->barcode,
                        'mpn' => $product->mpn,
                        'variant_name' => $variant['variant_name'],
                        'variant_details' => $variant['variant_details'] ?? null,
                        'inherits_selling_price' => $inheritsSellingPrice,
                        'image_path' => null,
                        'base_price' => $variant['base_price'] ?? $product->base_price,
                        'selling_price' => $inheritsSellingPrice
                            ? $product->selling_price
                            : $variant['selling_price'],
                        'warranty_months' => $product->warranty_months,
                        'default_tax_id' => $product->default_tax_id,
                        'default_discount_id' => $product->default_discount_id,
                        'description' => $variant['variant_details'] ?? $product->description,
                        'is_service' => $product->is_service,
                        'is_product' => $product->is_product,
                        'default_acquisition_type' => $product->default_acquisition_type->value,
                        'reorder_level' => $product->reorder_level,
                        'reorder_quantity' => $product->reorder_quantity,
                        'created_at' => $now,
                        'updated_at' => $now,
                        'deleted_at' => null,
                    ];
                })->values()->all();

                if ($rows !== []) {
                    Product::query()->insert($rows);
                }

                $user = $request->user();
                abort_if($user === null, 403);
                $attachmentRows = [];
                foreach ($uploadedImages as $uploadedImage) {
                    try {
                        $filePath = $this->storeUpload($uploadedImage, "products/$product->id", self::PUBLIC_DISK);
                    } catch (RuntimeException) {
                        throw ValidationException::withMessages([
                            'images' => __('products.validation.attachment_upload_failed'),
                        ]);
                    }
                    $storedPaths[] = $filePath;
                    $attachmentRows[] = [
                        'id' => (string) Str::uuid7(),
                        'product_id' => $product->id,
                        'file_path' => $filePath,
                        'original_name' => $uploadedImage->getClientOriginalName(),
                        'mime_type' => $uploadedImage->getMimeType(),
                        'file_size' => (int) $uploadedImage->getSize(),
                        'uploaded_by' => $user->id,
                        'uploaded_by_name' => $user->name,
                        'created_at' => $now,
                        'updated_at' => $now,
                        'deleted_at' => null,
                    ];
                }
                if ($attachmentRows !== []) {
                    ProductAttachment::query()->insert($attachmentRows);
                }
            });
        } catch (Throwable $throwable) {
            $this->deleteDocuments($storedPaths);

            throw $throwable;
        }

        return redirect()
            ->route('inventory.products.index')
            ->with('success', FlashMessage::created(FileResources::PRODUCTS));
    }

    /**
     * Show the form for editing an open resource.
     * Show the form for editing an existing product.
     *
     * @param  Product  $product  The product resource instance.
     * @return View Returns the rendered Blade view.
     */
    public function edit(Product $product): View
    {
        $this->allows(FileResources::PRODUCTS, FileAction::UPDATE);

        $product->load(['brand', 'category', 'type', 'variants', 'attachments']);
        $taxes = Tax::query()->where('is_active', true)->orderBy('code')->get();
        $discounts = Discount::query()->where('is_active', true)->orderBy('code')->get();
        $brands = Brand::query()
            ->where(fn ($query) => $query->where('is_active', true)->orWhereKey($product->brand_id))
            ->orderBy('name')->get();
        $suppliers = Company::query()
            ->where(fn ($query) => $query->where('is_creditor', true)->orWhereKey($product->supplier_company_id))
            ->orderBy('name')->get(['id', 'name']);
        $categories = ProductCategory::query()
            ->where(function ($query) use ($product): void {
                /** @var Builder<ProductCategory> $query */
                $query->where('is_active', true)->orWhereKey($product->product_category_id);
            })
            ->with(['types' => function ($query) use ($product): void {
                /** @var Builder<ProductType> $query */
                $query->where(function ($typeQuery) use ($product): void {
                    /** @var Builder<ProductType> $typeQuery */
                    $typeQuery->where('is_active', true)->orWhereKey($product->product_type_id);
                });
            }])
            ->orderBy('name')
            ->get();

        return view('inventory.products.edit', compact('product', 'taxes', 'discounts', 'brands', 'suppliers', 'categories'));
    }

    /**
     * Update an existing product or service.
     *
     * @param  UpdateProductRequest  $request  The validated request instance.
     * @param  Product  $product  The product resource instance.
     * @return RedirectResponse Redirects to the specified route.
     *
     * @throws Throwable
     */
    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $data = ProductData::from($request->validated());

        $variants = $request->validated('variants', []);
        if (! is_array($variants)) {
            throw ValidationException::withMessages([
                'variants' => __('validation.array', ['attribute' => __('products.tabs.variants')]),
            ]);
        }
        $variantRows = collect($variants)
            ->filter(fn (mixed $variant): bool => is_array($variant))
            ->filter(
                fn (array $variant): bool => filled($variant['variant_name'] ?? null)
                    || filled($variant['sku'] ?? null)
            )
            ->values();
        $variantIds = $variantRows->pluck('id')->filter()->values();
        $ownedVariantCount = Product::query()
            ->where('parent_product_id', $product->id)
            ->whereIn('id', $variantIds)
            ->count();
        if ($ownedVariantCount !== $variantIds->count()) {
            throw ValidationException::withMessages([
                'variants' => __('products.validation.invalid_variant'),
            ]);
        }
        if ($variantRows->contains(
            function (array $variant) use ($data): bool {
                $variantSku = $variant['sku'] ?? '';

                return strcasecmp(is_string($variantSku) ? $variantSku : '', (string) $data->sku) === 0;
            }
        )) {
            throw ValidationException::withMessages([
                'variants' => __('products.validation.variant_sku_matches_product'),
            ]);
        }
        $conflictingSku = Product::query()
            ->whereIn('sku', $variantRows->pluck('sku'))
            ->whereNotIn('id', $variantIds->push($product->id))
            ->exists();
        if ($conflictingSku) {
            throw ValidationException::withMessages([
                'variants' => __('products.validation.variant_sku_exists'),
            ]);
        }
        foreach ($variantRows as $index => $variant) {
            if (! ($variant['inherits_selling_price'] ?? false) && ! isset($variant['selling_price'])) {
                throw ValidationException::withMessages([
                    "variants.$index.selling_price" => __('products.validation.variant_selling_price_required'),
                ]);
            }
        }
        $uploadedImages = $request->file('images', []);
        if (! is_array($uploadedImages)) {
            $uploadedImages = [];
        }
        $removedAttachmentIds = $request->validated('removed_attachment_ids', []);
        if (! is_array($removedAttachmentIds)) {
            $removedAttachmentIds = [];
        }
        $removedAttachmentIds = array_values(array_filter($removedAttachmentIds, is_string(...)));
        $ownedAttachmentCount = ProductAttachment::query()
            ->where('product_id', $product->id)
            ->whereIn('id', $removedAttachmentIds)
            ->count();
        if ($ownedAttachmentCount !== count($removedAttachmentIds)) {
            throw ValidationException::withMessages([
                'removed_attachment_ids' => __('products.validation.invalid_attachment'),
            ]);
        }
        $remainingAttachmentCount = $product->attachments->count() - $ownedAttachmentCount + count($uploadedImages);
        if ($remainingAttachmentCount > 10) {
            throw ValidationException::withMessages([
                'images' => __('products.validation.maximum_attachments', ['max' => 10]),
            ]);
        }
        $isService = (bool) $data->isService;
        $data->warrantyMonths = $data->warrantyMonths === -1 ? null : $data->warrantyMonths;
        $acquisitionType = $isService
            ? AcquisitionType::EXPENSE
            : ($data->defaultAcquisitionType ?? AcquisitionType::EXPENSE);
        $reorderLevel = DecimalService::normalize(
            $acquisitionType === AcquisitionType::STOCK
                ? $data->reorderLevel ?? '0'
                : '0'
        );
        $reorderQuantity = DecimalService::normalize(
            $acquisitionType === AcquisitionType::STOCK
                ? $data->reorderQuantity ?? '0'
                : '0'
        );

        $storedPaths = [];
        try {
            DB::transaction(function () use (
                $acquisitionType,
                $data,
                $product,
                $removedAttachmentIds,
                $reorderLevel,
                $reorderQuantity,
                $request,
                &$storedPaths,
                $uploadedImages,
                $variantRows
            ): void {
                $product->name = $data->name ?? '';
                $product->sku = $data->sku ?? '';
                $product->barcode = $data->barcode ?? '';
                $product->mpn = $data->mpn ?? '';
                $product->brand_id = $data->brandId;
                $product->supplier_company_id = $data->supplierCompanyId;
                $product->product_category_id = $data->productCategoryId;
                $product->product_type_id = $data->productTypeId;
                $product->base_price = DecimalService::normalize($data->basePrice ?? '0');
                $product->selling_price = DecimalService::normalize($data->sellingPrice ?? '0');
                $product->warranty_months = $data->warrantyMonths;
                $product->default_tax_id = $data->defaultTaxId;
                $product->default_discount_id = $data->defaultDiscountId;
                $product->description = $data->description ?? '';
                $product->is_service = $data->isService ?? false;
                $product->is_product = $data->isProduct ?? false;
                $product->default_acquisition_type = $acquisitionType;
                $product->reorder_level = $reorderLevel;
                $product->reorder_quantity = $reorderQuantity;
                $product->save();

                $now = now();
                $rows = $variantRows->map(function (array $variant) use ($now, $product): array {
                    $inheritsSellingPrice = (bool) ($variant['inherits_selling_price'] ?? false);

                    return [
                        'id' => $variant['id'] ?? (string) Str::uuid7(),
                        'parent_product_id' => $product->id,
                        'brand_id' => $product->brand_id,
                        'product_category_id' => $product->product_category_id,
                        'product_type_id' => $product->product_type_id,
                        'name' => $product->name.' - '.(is_string($variant['variant_name'] ?? null) ? $variant['variant_name'] : ''),
                        'sku' => $variant['sku'],
                        'barcode' => $product->barcode,
                        'mpn' => $product->mpn,
                        'variant_name' => $variant['variant_name'],
                        'variant_details' => $variant['variant_details'] ?? null,
                        'inherits_selling_price' => $inheritsSellingPrice,
                        'image_path' => null,
                        'base_price' => $variant['base_price'] ?? $product->base_price,
                        'selling_price' => $inheritsSellingPrice
                            ? $product->selling_price
                            : $variant['selling_price'],
                        'warranty_months' => $product->warranty_months,
                        'default_tax_id' => $product->default_tax_id,
                        'default_discount_id' => $product->default_discount_id,
                        'description' => $variant['variant_details'] ?? $product->description,
                        'is_service' => $product->is_service,
                        'is_product' => $product->is_product,
                        'default_acquisition_type' => $product->default_acquisition_type->value,
                        'reorder_level' => $product->reorder_level,
                        'reorder_quantity' => $product->reorder_quantity,
                        'created_at' => $now,
                        'updated_at' => $now,
                        'deleted_at' => null,
                    ];
                })->all();
                $submittedIds = collect($rows)->pluck('id');

                Product::query()
                    ->where('parent_product_id', $product->id)
                    ->whereNotIn('id', $submittedIds)
                    ->delete();

                if ($rows !== []) {
                    Product::query()->upsert($rows, ['id'], [
                        'brand_id', 'product_category_id', 'product_type_id', 'name', 'sku', 'barcode', 'mpn', 'variant_name', 'variant_details', 'inherits_selling_price',
                        'base_price', 'selling_price', 'warranty_months', 'default_tax_id', 'default_discount_id',
                        'description', 'is_service', 'is_product', 'default_acquisition_type',
                        'reorder_level', 'reorder_quantity', 'updated_at', 'deleted_at',
                    ]);
                }

                if ($removedAttachmentIds !== []) {
                    ProductAttachment::query()
                        ->where('product_id', $product->id)
                        ->whereIn('id', $removedAttachmentIds)
                        ->delete();
                }

                $user = $request->user();
                abort_if($user === null, 403);
                $attachmentRows = [];
                foreach ($uploadedImages as $uploadedImage) {
                    try {
                        $filePath = $this->storeUpload($uploadedImage, "products/$product->id", self::PUBLIC_DISK);
                    } catch (RuntimeException) {
                        throw ValidationException::withMessages([
                            'images' => __('products.validation.attachment_upload_failed'),
                        ]);
                    }
                    $storedPaths[] = $filePath;
                    $attachmentRows[] = [
                        'id' => (string) Str::uuid7(),
                        'product_id' => $product->id,
                        'file_path' => $filePath,
                        'original_name' => $uploadedImage->getClientOriginalName(),
                        'mime_type' => $uploadedImage->getMimeType(),
                        'file_size' => (int) $uploadedImage->getSize(),
                        'uploaded_by' => $user->id,
                        'uploaded_by_name' => $user->name,
                        'created_at' => $now,
                        'updated_at' => $now,
                        'deleted_at' => null,
                    ];
                }
                if ($attachmentRows !== []) {
                    ProductAttachment::query()->insert($attachmentRows);
                }
            });
        } catch (Throwable $throwable) {
            $this->deleteDocuments($storedPaths);

            throw $throwable;
        }

        return redirect()
            ->route('inventory.products.index')
            ->with('success', FlashMessage::updated(FileResources::PRODUCTS));
    }

    /**
     * Remove a resource by its object.
     *
     * Refuses to delete a product that is already referenced by a document or a
     * stock movement. The rows themselves are only soft deleted, so the
     * reference would survive and every historical document, ledger entry and
     * stock report would keep pointing at a product that no longer appears in
     * any list.
     *
     * Attachments and variants are not counted as usage: they belong to the
     * product and are removed with it.
     *
     * @param  Product  $product  The product resource instance.
     * @return RedirectResponse Redirects to the specified route.
     */
    public function destroy(Product $product): RedirectResponse
    {
        $this->allows(FileResources::PRODUCTS, FileAction::REMOVE);

        $blocked = $this->productUsage($product);

        if ($blocked !== []) {
            throw ValidationException::withMessages([
                'product' => __('products.validation.in_use', [
                    'references' => implode(', ', $blocked),
                ]),
            ]);
        }

        ProductAttachment::query()->where('product_id', $product->id)->delete();
        Product::query()->where('parent_product_id', $product->id)->delete();
        $product->delete();

        return redirect()
            ->route('inventory.products.index')
            ->with('success', FlashMessage::deleted(FileResources::PRODUCTS));
    }

    /**
     * List the documents and movements that still reference a product.
     *
     * Soft-deleted rows are included so a previously removed document cannot
     * free a product for deletion and leave a dangling reference behind.
     *
     * @param  Product  $product  The product resource instance.
     * @return list<string> Human readable names of the blocking references.
     */
    private function productUsage(Product $product): array
    {
        $id = $product->id;

        $checks = [
            __('products.usage.stock_movements') => DB::table('product_ledgers')->where('product_id', $id),
            __('products.usage.ledger_entries') => DB::table('ledgers')->where('product_id', $id),
            // The sub-ledgers are the customer and supplier record, so they are
            // what says this product still appears on an open account.
            __('products.usage.customer_ledger_entries') => DB::table('ar_ledgers')->where('product_id', $id),
            __('products.usage.supplier_ledger_entries') => DB::table('ap_ledgers')->where('product_id', $id),
            __('products.usage.purchase_order_lines') => DB::table('purchase_order_lines')->where('product_id', $id),
            __('products.usage.sales_order_lines') => DB::table('sales_order_lines')->where('product_id', $id),
            __('products.usage.delivery_order_lines') => DB::table('delivery_order_lines')->where('product_id', $id),
            __('products.usage.quotation_lines') => DB::table('quotation_lines')->where('product_id', $id),
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
