<?php

/**
 * ============================================================================
 *
 * @author      redfluz
 *
 * @github      https://github.com/redfluz
 *
 * @youtube     https://www.youtube.com/@redfluz
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

declare(strict_types=1);

namespace App\Http\Requests\Sales\SalesOrder;

use App\Enums\DiscountType;
use App\Enums\FileAction;
use App\Enums\FileResources;
use App\Enums\TaxMode;
use App\Enums\UploadType;
use App\Models\Inventory\Product;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * The fields a sales order is created from.
 *
 * The rules live here rather than in the controller so that the controller can be
 * handed a request that has already been checked, and can ask for a named field
 * instead of reaching into an array it built itself. A rule stated once cannot be
 * stated twice in two places that then disagree.
 *
 * The attachment is offered on creation and not on edit, because the file is
 * attached to the order when it is raised; replacing it later is a separate act
 * with its own history, and an edit that quietly took a new file would leave two
 * files and one of them unexplained.
 */
class StoreSalesOrderRequest extends SalesOrderRequest
{
    /**
     * Who may raise a sales order.
     *
     * The permission lives here rather than in the controller, so that the request
     * says everything about who may send it and what it must contain. Laravel
     * asks this before the controller runs, so a request that is not permitted
     * never reaches the action it was meant to guard.
     *
     * It refuses by redirecting with an explanation and writing the attempt to
     * the log, which is what this check has always done and what returning a
     * plain false would lose.
     */
    public function authorize(): bool
    {
        return $this->allows(FileResources::SALES_ORDERS, FileAction::ADD);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'company_id' => ['required', 'uuid', Rule::exists('companies', 'id')->where('is_debtor', true)],
            'contact_id' => ['nullable', 'uuid', 'exists:contacts,id'],
            'customer_reference_number' => ['nullable', 'string', 'max:255'],
            'order_date' => ['required', 'date'],
            'expected_delivery_date' => ['nullable', 'date', 'after_or_equal:order_date'],
            'notes' => ['nullable', 'string'],
            'attachment' => ['nullable', 'file', UploadType::DOCUMENT_AND_IMAGES->rule(), 'max:5120'],
            'lines' => ['required', 'array', 'max:20'],
            'lines.*.product_id' => ['nullable', 'uuid', 'exists:products,id'],
            'lines.*.description' => ['nullable', 'string'],
            'lines.*.quantity' => ['nullable', 'numeric', 'min:0.0001'],
            'lines.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'lines.*.warranty_months' => ['nullable', 'integer', 'min:-1', 'max:120'],
            'lines.*.discount_option' => ['nullable', 'string'],
            'lines.*.discount_type' => ['nullable', Rule::enum(DiscountType::class)],
            'lines.*.discount_value' => ['nullable', 'numeric', 'min:0'],
            'lines.*.tax_option' => ['nullable', 'string'],
            'lines.*.tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'lines.*.tax_mode' => ['nullable', Rule::enum(TaxMode::class)],
        ];
    }

    /**
     * Validate that products with variants are not used directly.
     *
     * When a product has variants, the user must select a specific variant
     * instead of the parent product. This prevents confusion about which
     * variant's stock is being reserved.
     */
    public function after(): \Closure
    {
        return function (Validator $validator): void {
            $lines = $this->input('lines', []);
            if (! is_array($lines)) {
                return;
            }

            foreach ($lines as $index => $line) {
                if (! is_array($line)) {
                    continue;
                }

                $productId = $line['product_id'] ?? null;
                if (empty($productId)) {
                    continue;
                }

                $product = Product::query()
                    ->whereKey($productId)
                    ->first();

                if ($product === null) {
                    continue;
                }

                // Check if this product has variants
                $variantCount = Product::query()
                    ->where('parent_product_id', $product->id)
                    ->count();

                if ($variantCount > 0) {
                    $validator->errors()->add(
                        "lines.{$index}.product_id",
                        __('sales_orders.validation.parent_product_has_variants', [
                            'product' => $product->name,
                        ])
                    );
                }
            }
        };
    }
}
