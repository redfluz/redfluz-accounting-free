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

namespace App\Support\Data\Sales;

use App\Models\Sales\SalesAdjustmentLine;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;

/**
 * One line of a sales adjustment: one side of the correction, against a product.
 *
 * An adjustment's entry has two sides, so a correction has two lines: one
 * carrying the debit account and one carrying the credit account. They are lines
 * of the adjustment rather than a separate concept, which is why this carries the
 * account columns as well as the product.
 *
 * A line carries either a debit or a credit, never both. That is what lets the two
 * sides add to the same figure without every row holding an amount in both
 * directions, and it is why a correction that touches more than one account is
 * representable at all.
 *
 * The account names are kept alongside the ids rather than resolved on read, so a
 * report printed later reads the names that were correct then even if the chart of
 * accounts has been renamed since.
 *
 * @param  numeric-string  $unitPrice
 * @param  numeric-string  $subtotalAmount
 * @param  numeric-string  $discountRate
 * @param  numeric-string  $discountAmount
 * @param  numeric-string  $taxRate
 * @param  numeric-string  $taxAmount
 * @param  numeric-string  $lineTotalAmount
 * @param  numeric-string  $debitAmount
 * @param  numeric-string  $creditAmount
 * @param  numeric-string  $totalAmount
 */
class SalesAdjustmentLineData extends Data
{
    public function __construct(
        #[MapName('id')]
        public string $id,

        #[MapName('sales_adjustment_id')]
        public string $salesAdjustmentId,

        #[MapName('product_id')]
        public ?string $productId,

        #[MapName('product_name')]
        public ?string $productName,

        // One side of the entry. Never both on the same line.
        #[MapName('debit_id')]
        public ?string $debitId,

        #[MapName('credit_id')]
        public ?string $creditId,

        #[MapName('debit_acc')]
        public ?string $debitAcc,

        #[MapName('credit_acc')]
        public ?string $creditAcc,

        #[MapName('debit_acc_desc')]
        public ?string $debitAccDesc,

        #[MapName('credit_acc_desc')]
        public ?string $creditAccDesc,

        #[MapName('quantity')]
        public ?int $quantity,

        #[MapName('unit_price')]
        public ?string $unitPrice,

        #[MapName('subtotal_amount')]
        public ?string $subtotalAmount,

        #[MapName('discount_rate')]
        public ?string $discountRate,

        #[MapName('discount_amount')]
        public ?string $discountAmount,

        #[MapName('tax_rate')]
        public ?string $taxRate,

        #[MapName('tax_amount')]
        public ?string $taxAmount,

        #[MapName('tax_mode')]
        public ?string $taxMode,

        #[MapName('line_total_amount')]
        public ?string $lineTotalAmount,

        #[MapName('debit_amount')]
        public ?string $debitAmount,

        #[MapName('credit_amount')]
        public ?string $creditAmount,

        #[MapName('total_amount')]
        public ?string $totalAmount,

        #[MapName('description')]
        public ?string $description,

        #[MapName('created_at')]
        public ?CarbonImmutable $createdAt,

        #[MapName('updated_at')]
        public ?CarbonImmutable $updatedAt,

        #[MapName('deleted_at')]
        public ?CarbonImmutable $deletedAt,
    ) {}

    /**
     * The same moment, as the type this object holds.
     *
     * A model hands back whatever Carbon its cast produced; this object holds one
     * concrete type so that a form and a model produce the same thing. The value
     * is the same either way, and only its class differs.
     */
    private static function moment(?CarbonInterface $value): ?CarbonImmutable
    {
        return $value === null ? null : CarbonImmutable::instance($value);
    }

    /**
     * Build from the model, which has already cast its own columns.
     *
     * Not Data::from($model->getAttributes()): that reads the raw row, where a
     * date is the string the database stores rather than the date object the
     * model holds, and the two are written in different formats. Taking the
     * model's attributes instead means the cast is what is mapped.
     */
    public static function fromModel(SalesAdjustmentLine $model): self
    {
        // A line records product detail only. The debit and credit accounts
        // live on the adjustment's chart rows, not on the line, so there is
        // nothing on the model to read them from.
        return new self(
            id: $model->id,
            salesAdjustmentId: $model->sales_adjustment_id,
            productId: $model->product_id,
            productName: $model->product_name,
            debitId: null,
            creditId: null,
            debitAcc: null,
            creditAcc: null,
            debitAccDesc: null,
            creditAccDesc: null,
            quantity: $model->quantity,
            unitPrice: $model->unit_price,
            subtotalAmount: $model->subtotal_amount,
            discountRate: $model->discount_rate,
            discountAmount: $model->discount_amount,
            taxRate: $model->tax_rate,
            taxAmount: $model->tax_amount,
            taxMode: $model->tax_mode,
            lineTotalAmount: $model->line_total_amount,
            debitAmount: null,
            creditAmount: null,
            totalAmount: $model->total_amount,
            description: $model->description,
            createdAt: self::moment($model->created_at),
            updatedAt: self::moment($model->updated_at),
            deletedAt: self::moment($model->deleted_at),
        );
    }

    /**
     * The row as the sales_adjustment_lines table wants it.
     *
     * @return array<string, mixed>
     */
    public function toRow(): array
    {
        /** @var array<string, mixed> $row */
        $row = [
            ...$this->toArray(),
            'created_at' => $this->createdAt?->format('Y-m-d H:i:s'),
        ];

        return $row;
    }
}
