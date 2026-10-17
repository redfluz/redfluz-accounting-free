<?php

namespace App\Services;

use App\Enums\ProductMovementDirection;
use App\Enums\ProductMovementStatus;
use App\Enums\ProductStockBucket;
use App\Models\Inventory\Product;
use App\Models\Inventory\ProductLedger;
use Illuminate\Support\Collection;

class DashboardInventoryService
{
    /**
     * @return array{productCount: int, availableUnits: float, inventoryValue: float, lowStockCount: int, pendingUnits: float, categoryCount: int, lowStockProducts: Collection<int, Product>, recentMovements: \Illuminate\Database\Eloquent\Collection<int, ProductLedger>}
     */
    public function getDashboardData(): array
    {
        $products = Product::query()
            ->whereNull('parent_product_id')
            ->where('is_product', true)
            ->where('is_service', false)
            ->with(['category', 'type', 'supplier'])
            ->orderBy('name')
            ->get();

        $balances = ProductLedger::query()
            ->where('stock_bucket', ProductStockBucket::SALEABLE->value)
            ->select('product_id')
            ->selectRaw('COALESCE(SUM(CASE WHEN status = ? AND direction = ? THEN quantity WHEN status = ? AND direction = ? THEN -quantity ELSE 0 END), 0) as available_quantity', [
                ProductMovementStatus::ACTIVE->value, ProductMovementDirection::IN->value,
                ProductMovementStatus::ACTIVE->value, ProductMovementDirection::OUT->value,
            ])
            ->selectRaw('COALESCE(SUM(CASE WHEN status = ? AND direction = ? THEN quantity ELSE 0 END), 0) as pending_in_quantity', [ProductMovementStatus::PENDING->value, ProductMovementDirection::IN->value])
            ->selectRaw('COALESCE(SUM(CASE WHEN status = ? AND direction = ? THEN quantity ELSE 0 END), 0) as pending_out_quantity', [ProductMovementStatus::PENDING->value, ProductMovementDirection::OUT->value])
            ->groupBy('product_id')
            ->get()
            ->keyBy('product_id');

        $products->each(function (Product $product) use ($balances): void {
            /** @var object{available_quantity?: mixed, pending_in_quantity?: mixed, pending_out_quantity?: mixed}|null $balance */
            $balance = $balances->get($product->id);
            $availableQty = is_object($balance) && isset($balance->available_quantity) && is_numeric($balance->available_quantity) ? (float) $balance->available_quantity : 0.0;
            $product->setAttribute('available_quantity', $availableQty);
            $pendingIn = is_object($balance) && isset($balance->pending_in_quantity) && is_numeric($balance->pending_in_quantity) ? (float) $balance->pending_in_quantity : 0.0;
            $product->setAttribute('pending_in_quantity', $pendingIn);
            $pendingOut = is_object($balance) && isset($balance->pending_out_quantity) && is_numeric($balance->pending_out_quantity) ? (float) $balance->pending_out_quantity : 0.0;
            $product->setAttribute('pending_out_quantity', $pendingOut);
            $basePrice = (float) $product->base_price;
            $product->setAttribute('stock_value', $basePrice * $availableQty);
        });

        $lowStock = $products->filter(function (Product $product): bool {
            $reorderQty = (float) $product->reorder_quantity;
            $availableQtyRaw = $product->getAttribute('available_quantity') ?? 0;
            $availableQty = is_numeric($availableQtyRaw) ? (float) $availableQtyRaw : 0.0;
            $reorderLevel = (float) $product->reorder_level;

            return $reorderQty > 0 && $availableQty <= $reorderLevel;
        })->sortBy('available_quantity')->take(8)->values();
        $recentMovements = ProductLedger::query()->with(['product', 'company'])->latest('date')->latest('created_at')->limit(8)->get();

        $availableUnits = $products->sum('available_quantity');
        $inventoryValue = $products->sum('stock_value');
        $pendingIn = $products->sum('pending_in_quantity');
        $pendingOut = $products->sum('pending_out_quantity');

        return [
            'productCount' => $products->count(),
            'availableUnits' => is_numeric($availableUnits) ? (float) $availableUnits : 0.0,
            'inventoryValue' => is_numeric($inventoryValue) ? (float) $inventoryValue : 0.0,
            'lowStockCount' => $lowStock->count(),
            'pendingUnits' => (is_numeric($pendingIn) ? (float) $pendingIn : 0.0) + (is_numeric($pendingOut) ? (float) $pendingOut : 0.0),
            'categoryCount' => $products->pluck('product_category_id')->filter()->unique()->count(),
            'lowStockProducts' => $lowStock,
            'recentMovements' => $recentMovements,
        ];
    }
}
