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

namespace App\Traits;

use App\Enums\LedgerStatus;
use App\Enums\LedgerSubType;
use App\Enums\LedgerType;
use App\Enums\ProductMovementDirection;
use App\Enums\ProductMovementSource;
use App\Enums\ProductMovementStatus;
use App\Enums\ProductStockBucket;
use App\Models\ChartOfAccount\Chart;
use App\Models\ChartOfAccount\ChartSetting;
use App\Models\Finance\Ledger;
use App\Models\Inventory\Product;
use App\Models\Inventory\ProductLedger;
use App\Models\Inventory\StockLocation;
use App\Models\Sales\SalesInvoice;
use App\Services\DecimalService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

trait HandlesInvoiceProductMovement
{
    /**
     * Ensure a draft sales invoice does not reserve more physical stock than is available.
     *
     * @param  iterable<array-key, array{product_id?: mixed, quantity?: mixed, ...}>  $lines  The invoice line items.
     * @param  string  $documentNumber  The sales invoice document number.
     * @param  string|null  $stockLocationId  The optional stock location ID.
     * @return void No return value; throws when stock is insufficient.
     *
     * @throws ValidationException
     */
    protected function ensureSalesStockCanBeReserved(
        iterable $lines,
        string $documentNumber,
        ?string $stockLocationId = null
    ): void {
        $stockLocation = $this->resolveStockLocation($stockLocationId);
        $requestedQuantities = collect($lines)
            ->filter(fn (array $line): bool => filled($line['product_id'] ?? null))
            ->groupBy('product_id')
            ->map(
                fn (Collection $productLines): string => $productLines->reduce(
                    fn (string $quantity, array $line): string => bcadd(
                        $quantity,
                        DecimalService::normalize(is_numeric($line['quantity'] ?? null) ? (string) $line['quantity'] : '0'),
                        4
                    ),
                    '0.0000'
                )
            );

        if ($requestedQuantities->isEmpty()) {
            return;
        }

        $products = Product::query()
            ->whereIn('id', $requestedQuantities->keys())
            ->where('is_service', false)
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        if ($products->isEmpty()) {
            return;
        }

        // For parent products, also include their variants in the stock check.
        // This allows sales orders to use the parent product and draw stock from
        // any variant.
        $productIds = [];
        $variantIdsByParent = [];
        foreach ($products as $product) {
            /** @var Product $product */
            $productIds[] = $product->id;
            $variantIds = Product::query()
                ->where('parent_product_id', $product->id)
                ->get(['id'])
                ->map(fn (Product $variant): string => $variant->id)
                ->all();
            if (! empty($variantIds)) {
                $variantIdsByParent[$product->id] = $variantIds;
                $productIds = array_merge($productIds, $variantIds);
            }
        }
        $productIds = array_values(array_unique($productIds));

        $movements = ProductLedger::query()
            ->whereIn('product_id', $productIds)
            ->where('stock_location_id', $stockLocation->id)
            ->where('stock_bucket', ProductStockBucket::SALEABLE->value)
            ->where(function ($query) use ($documentNumber): void {
                $query
                    ->where('status', ProductMovementStatus::ACTIVE->value)
                    ->orWhere(function ($pendingQuery) use ($documentNumber): void {
                        $pendingQuery
                            ->where('status', ProductMovementStatus::PENDING->value)
                            ->where('direction', ProductMovementDirection::OUT->value)
                            ->where('source_type', '<>', ProductMovementSource::SALES_ORDER->value)
                            ->where('document_number', '<>', $documentNumber);
                    });
            })
            ->orderBy('product_id')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();
        /**
         * @var Product $product
         */
        foreach ($products as $productId => $product) {
            // Include movements from the parent product and all its variants.
            $relatedProductIds = array_merge([$productId], $variantIdsByParent[$productId] ?? []);
            $productMovements = $movements->whereIn('product_id', $relatedProductIds);

            $activeQuantity = $productMovements
                ->where('status', ProductMovementStatus::ACTIVE)
                ->reduce(function (string $balance, ProductLedger $movement): string {
                    if ($movement->direction === ProductMovementDirection::IN) {
                        return bcadd($balance, (string) $movement->quantity, 4);
                    }

                    return bcsub($balance, (string) $movement->quantity, 4);
                }, '0.0000');

            $reservedQuantity = $productMovements
                ->where('status', ProductMovementStatus::PENDING)
                ->reduce(
                    fn (string $quantity, ProductLedger $movement): string => bcadd(
                        $quantity,
                        (string) $movement->quantity,
                        4
                    ),
                    '0.0000'
                );

            $availableQuantity = bcsub($activeQuantity, $reservedQuantity, 4);
            $requestedQuantity = (string) $requestedQuantities->get($productId, '0.0000');

            if (bccomp($requestedQuantity, $availableQuantity, 4) === 1) {
                throw ValidationException::withMessages([
                    'lines' => __('product_ledgers.validation.insufficient_stock_reservation', [
                        'product' => $product->name,
                        'requested' => number_format((float) $requestedQuantity, 4),
                        'available' => number_format((float) max(0, (float) $availableQuantity), 4),
                    ]),
                ]);
            }
        }
    }

    /**
     * Rebuild pending stock-out rows from one unposted sales invoice.
     *
     * @param  string  $documentNumber  The sales invoice document number.
     * @param  string|null  $stockLocationId  The optional stock location ID.
     * @return void No return value; rebuilds the pending movements in the database.
     */
    protected function syncPendingSalesProductMovements(
        string $documentNumber,
        ?string $stockLocationId = null
    ): void {
        $stockLocation = $this->resolveStockLocation($stockLocationId);
        ProductLedger::query()
            ->where('document_number', $documentNumber)
            ->where('source_type', ProductMovementSource::SALES_INVOICE)
            ->where('status', ProductMovementStatus::PENDING->value)
            ->forceDelete();
        // Invoice lines are read from the invoice's own table, where each line
        // belongs to the invoice it was raised on.
        $invoice = SalesInvoice::query()
            ->where('document_number', $documentNumber)
            ->first();

        if ($invoice === null) {
            return;
        }

        $invoiceLines = $invoice->lines()->with('product')->whereNotNull('product_id')->get();

        $sourceType = ProductMovementSource::SALES_INVOICE->value;
        $direction = ProductMovementDirection::OUT->value;
        $status = ProductMovementStatus::PENDING->value;
        $now = now();
        // Movements carry a plain date so they sort against goods receipts,
        // which are also date only.
        $invoiceDate = Carbon::parse($invoice->date)->toDateString();

        $data = [];

        foreach ($invoiceLines as $invoiceLine) {
            $product = $invoiceLine->product;

            if (! $product instanceof Product || $product->is_service) {
                continue;
            }

            $basePriceRaw = $product->base_price;
            $unitCost = DecimalService::normalize((string) $basePriceRaw);
            $quantityRaw = $invoiceLine->quantity ?? 0;
            $quantity = DecimalService::normalize((string) $quantityRaw);
            $totalCost = bcmul($quantity, $unitCost, 2);

            $data[] = [
                'id' => (string) Str::uuid7(),
                'product_id' => $product->id,
                'stock_location_id' => $stockLocation->id,
                'stock_location_name' => $stockLocation->name,
                'company_id' => $invoice->company_id,
                'source_entry_id' => $invoiceLine->id,
                'cogs_ledger_id' => (string) Str::uuid7(),
                'inventory_ledger_id' => (string) Str::uuid7(),
                'product_sku' => $product->sku,
                'product_name' => $invoiceLine->product_name ?? $product->name,
                'company_name' => $invoice->company_name,
                'document_number' => $documentNumber,
                'reference_number' => $invoice->reference_number,
                'source_type' => $sourceType,
                'direction' => $direction,
                'date' => $invoice->date,
                'quantity' => $invoiceLine->quantity,
                'unit_cost' => $unitCost,
                'total_cost' => $totalCost,
                'status' => $status,
                'notes' => $invoiceLine->description,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if ($data !== []) {
            ProductLedger::query()->insert($data);
        }
    }

    /**
     * Activate pending stock-out rows and create their COGS journal pair.
     *
     * @param  string  $documentNumber  The sales invoice document number whose movements should be posted.
     * @return void No return value; activates movements and creates COGS journal entries.
     *
     * @throws ValidationException
     */
    protected function postSalesProductMovements(string $documentNumber): void
    {
        /** @var \Illuminate\Database\Eloquent\Collection<int, ProductLedger> $movements */
        $movements = ProductLedger::query()
            ->where('document_number', $documentNumber)
            ->where('source_type', ProductMovementSource::SALES_INVOICE)
            ->where('status', ProductMovementStatus::PENDING->value)
            ->orderBy('product_id')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        if ($movements->isEmpty()) {
            return;
        }

        $this->ensureSufficientStock($movements);

        $chartSetting = ChartSetting::query()->first();
        $cogsChart = filled($chartSetting?->cogs_chart_uuid)
            ? Chart::query()->whereKey($chartSetting->cogs_chart_uuid)->first()
            : null;
        $inventoryChart = filled($chartSetting?->inventory_chart_uuid)
            ? Chart::query()->whereKey($chartSetting->inventory_chart_uuid)->first()
            : null;

        if ($cogsChart === null) {
            throw ValidationException::withMessages([
                'cogs_chart_uuid' => __('product_ledgers.validation.cogs_account_required'),
            ]);
        }

        if ($inventoryChart === null) {
            throw ValidationException::withMessages([
                'inventory_chart_uuid' => __('product_ledgers.validation.inventory_account_required'),
            ]);
        }

        foreach ($movements as $movement) {
            $common = [
                'source_id' => $movement->id,
                'source_type' => null,
                'company_id' => $movement->company_id,
                'company_name' => $movement->company_name,
                'contact_id' => null,
                'document_number' => $movement->document_number,
                'reference_number' => $movement->reference_number ?? $movement->document_number,
                'type' => LedgerType::JOURNAL->value,
                'subtype' => LedgerSubType::JOURNAL->value,
                'date' => $movement->date,
                'document_date' => $movement->date,
                'total_amount' => $movement->total_cost,
                'status' => LedgerStatus::POSTED->value,
                'notes' => __('product_ledgers.entries.cogs_posting', [
                    'document' => $movement->document_number,
                ]),
                'description' => $movement->notes,
            ];

            $cogsLedger = new Ledger;
            $cogsLedger->forceFill($common + [
                'id' => $movement->cogs_ledger_id,
                'debit_id' => $cogsChart->id,
                'credit_id' => null,
                'debit_acc' => $cogsChart->code,
                'credit_acc' => null,
                'debit_acc_desc' => $cogsChart->name,
                'credit_acc_desc' => null,
                'debit_amount' => $movement->total_cost,
                'credit_amount' => '0.00',
            ]);
            $cogsLedger->save();

            $inventoryLedger = new Ledger;
            $inventoryLedger->forceFill($common + [
                'id' => $movement->inventory_ledger_id,
                'debit_id' => null,
                'credit_id' => $inventoryChart->id,
                'debit_acc' => null,
                'credit_acc' => $inventoryChart->code,
                'debit_acc_desc' => null,
                'credit_acc_desc' => $inventoryChart->name,
                'debit_amount' => '0.00',
                'credit_amount' => $movement->total_cost,
            ]);
            $inventoryLedger->save();

            $movement->update([
                'status' => ProductMovementStatus::ACTIVE->value,
                'activated_at' => now(),
            ]);
        }
    }

    /**
     * Prevent a posted sales invoice from reducing physical stock below zero.
     *
     * @param  Collection<int, ProductLedger>  $pendingMovements  The pending product movements to validate.
     * @return void No return value; throws when stock is insufficient.
     *
     * @throws ValidationException
     */
    private function ensureSufficientStock(Collection $pendingMovements): void
    {
        $productIds = $pendingMovements
            ->pluck('product_id')
            ->unique()
            ->sort()
            ->values();
        $locationIds = $pendingMovements->pluck('stock_location_id')->unique()->values();

        Product::query()
            ->whereIn('id', $productIds)
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        $activeMovements = ProductLedger::query()
            ->whereIn('product_id', $productIds)
            ->whereIn('stock_location_id', $locationIds)
            ->where('stock_bucket', ProductStockBucket::SALEABLE->value)
            ->where('status', ProductMovementStatus::ACTIVE->value)
            ->orderBy('product_id')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        foreach ($pendingMovements->groupBy(
            fn (ProductLedger $movement): string => $movement->stock_location_id.'|'.$movement->product_id
        ) as $productMovements) {
            $firstMovement = $productMovements->first();
            if ($firstMovement === null) {
                continue;
            }

            $productId = (string) $firstMovement->product_id;
            $productName = (string) $firstMovement->product_name;
            $locationId = (string) $firstMovement->stock_location_id;

            $availableQuantity = $activeMovements
                ->where('product_id', $productId)
                ->where('stock_location_id', $locationId)
                ->reduce(function (string $balance, ProductLedger $movement): string {
                    if ($movement->direction === ProductMovementDirection::IN) {
                        return bcadd($balance, (string) $movement->quantity, 4);
                    }

                    return bcsub($balance, (string) $movement->quantity, 4);
                }, '0.0000');

            $requestedQuantity = $productMovements->reduce(
                fn (string $quantity, ProductLedger $movement): string => bcadd(
                    $quantity,
                    (string) $movement->quantity,
                    4
                ),
                '0.0000'
            );

            if (bccomp($requestedQuantity, $availableQuantity, 4) === 1) {
                throw ValidationException::withMessages([
                    'document_number' => __('product_ledgers.validation.insufficient_stock', [
                        'product' => $productName,
                        'requested' => number_format((float) $requestedQuantity, 4),
                        'available' => number_format((float) $availableQuantity, 4),
                    ]),
                ]);
            }
        }
    }

    /**
     * Resolve an active stock location for a stock movement.
     *
     * Stock locations are shared in this edition, so any active location may be
     * used. When no location is supplied the default warehouse is resolved.
     *
     * @param  string|null  $stockLocationId  The requested stock location, or null for the default.
     * @return StockLocation Returns the active stock location.
     *
     * @throws ValidationException
     */
    private function resolveStockLocation(?string $stockLocationId): StockLocation
    {
        $stockLocation = filled($stockLocationId)
            ? StockLocation::query()->whereKey($stockLocationId)->where('is_active', true)->first()
            : StockLocation::query()->where('is_active', true)->orderBy('name')->first();

        if ($stockLocation === null) {
            throw ValidationException::withMessages([
                'stock_location_id' => __('product_ledgers.validation.stock_location_required'),
            ]);
        }

        return $stockLocation;
    }
}
