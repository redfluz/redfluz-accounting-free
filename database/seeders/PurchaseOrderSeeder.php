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

/**
 * @noinspection ALL
 *
 * @phpstan-ignore-file
 */

namespace Database\Seeders;

use App\Enums\AcquisitionType;
use App\Enums\ChartReportGroupEnum;
use App\Enums\PurchaseOrderType;
use App\Models\ChartOfAccount\Chart;
use App\Models\ChartOfAccount\ChartSetting;
use App\Models\Crm\Company;
use App\Models\Crm\Contact;
use App\Models\Inventory\Product;
use App\Models\Purchase\PurchaseOrder;
use App\Models\Purchase\PurchaseOrderSetting;
use Carbon\CarbonInterface;
use Database\Seeders\Concerns\SeedsDocumentSequences;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;

class PurchaseOrderSeeder extends Seeder
{
    use SeedsDocumentSequences;

    public function run(): void
    {
        PurchaseOrderSetting::query()->updateOrCreate([], [
            'default_credit_term_days' => 30,
        ]);
        $suppliers = Company::query()
            ->whereIn('name', ['Ingram Micro Malaysia Sdn Bhd', 'VSTECS Berhad'])
            ->orderBy('name')
            ->get();
        $products = Product::query()->orderBy('name')->take(3)->get();
        if ($suppliers->isEmpty() || $products->isEmpty()) {
            return;
        }

        $this->seedOrder('po10001', $suppliers->first(), $products->take(2), now()->subDays(12));
        $this->seedOrder('po10002', $suppliers->last(), $products->skip(1)->take(2), now());

        $this->seedDocumentSequence('purchase_order', 'PO', 10003, 'purchase_orders', 'document_number');

    }

    /**
     * @param  string  $number  The purchase order number.
     * @param  Company  $supplier  The supplier company.
     * @param  Collection<int, Product>  $products
     * @param  CarbonInterface  $date  The purchase order date.
     */
    private function seedOrder(
        string $number,
        Company $supplier,
        Collection $products,
        CarbonInterface $date
    ): void {
        $contact = Contact::query()->where('company_id', $supplier->id)->first();
        $chartSetting = ChartSetting::query()->first();
        $fixedAssetChartId = Chart::query()
            ->where('report_group', ChartReportGroupEnum::PROPERTY_PLANT_EQUIPMENT->value)
            ->value('id');
        $order = PurchaseOrder::query()->withTrashed()->updateOrCreate(
            ['document_number' => $number],
            [
                'company_id' => $supplier->id,
                'contact_id' => $contact?->id,
                'purchase_type' => PurchaseOrderType::PURCHASE,
                'supplier_reference_number' => 'SUP-'.mb_strtoupper($number),
                'company_name' => $supplier->name,
                'contact_name' => $contact?->name,
                'date' => $date,
                'expected_date' => $date->copy()->addDays(14),
                'credit_term_days' => 30,
                'due_date' => $date->copy()->addDays(30),
                'subtotal' => '0.00',
                'tax_amount' => '0.00',
                'total_amount' => '0.00',
                'notes' => 'Draft sample PO remains editable.',
                'approved_at' => null,
                'deleted_at' => null,
            ]
        );

        $order->lines()->forceDelete();
        $subtotal = '0.00';
        foreach ($products as $index => $product) {
            $quantity = $index + 5;
            $unitPrice = number_format((float) $product->base_price, 2, '.', '');
            $total = bcmul((string) $quantity, $unitPrice, 2);
            $order->lines()->create([
                'product_id' => $product->id,
                'product_sku' => $product->sku,
                'product_name' => $product->name,
                'description' => $product->description,
                'is_service' => $product->is_service,
                'acquisition_type' => $product->is_service
                    ? AcquisitionType::EXPENSE->value
                    : $product->default_acquisition_type->value,
                'debit_chart_id' => match ($product->is_service
                    ? AcquisitionType::EXPENSE
                    : $product->default_acquisition_type) {
                    AcquisitionType::STOCK => $chartSetting?->inventory_chart_uuid,
                    AcquisitionType::EXPENSE => $chartSetting?->cogs_chart_uuid,
                    AcquisitionType::FIXED_ASSET => $fixedAssetChartId,
                },
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total_amount' => $total,
            ]);
            $subtotal = bcadd($subtotal, $total, 2);
        }
        $order->update(['subtotal' => $subtotal, 'total_amount' => $subtotal]);
    }
}
