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

namespace App\Models\Finance;

use App\Enums\AcquisitionType;
use App\Enums\DiscountType;
use App\Enums\LedgerLineType;
use App\Enums\LedgerStatus;
use App\Enums\LedgerSubType;
use App\Enums\LedgerType;
use App\Enums\PaymentTransferType;
use App\Enums\PurchaseOrderType;
use App\Enums\TaxMode;
use App\Models\Crm\Company;
use App\Models\Crm\Contact;
use App\Models\Inventory\Product;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Models\Concerns\HasActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @mixin Builder<Ledger>
 *
 * @property string $id
 * @property string|null $company_id
 * @property string|null $debit_id
 * @property string|null $credit_id
 * @property string|null $product_id
 * @property string|null $contact_id
 * @property string $document_number
 * @property string $reference_number
 * @property string|null $debit_acc
 * @property string|null $credit_acc
 * @property string|null $debit_acc_desc
 * @property string|null $credit_acc_desc
 * @property string|null $company_name
 * @property string|null $product_name
 * @property LedgerType $type
 * @property LedgerSubType $subtype
 * @property CarbonInterface $date
 * @property CarbonInterface|null $due_date
 * @property int $quantity
 * @property numeric-string $unit_price
 * @property int|null $warranty_months
 * @property LedgerLineType $line_type
 * @property numeric-string $subtotal_amount
 * @property string|null $discount_id
 * @property string|null $discount_name
 * @property DiscountType|null $discount_type
 * @property numeric-string $discount_value
 * @property numeric-string $discount_rate
 * @property numeric-string $discount_amount
 * @property string|null $tax_id
 * @property string|null $tax_name
 * @property numeric-string $tax_rate
 * @property numeric-string $tax_amount
 * @property TaxMode|null $tax_mode
 * @property numeric-string $line_total_amount
 * @property AcquisitionType|null $acquisition_type
 * @property numeric-string $debit_amount
 * @property numeric-string $credit_amount
 * @property numeric-string $total_amount
 * @property string|null $attachment_path
 * @property LedgerStatus $status
 * @property string|null $notes
 * @property PaymentTransferType|null $payment_transfer_type
 * @property string|null $description
 * @property CarbonInterface|null $deleted_at
 * @property CarbonInterface $created_at
 * @property CarbonInterface $updated_at
 *
 * @noinspection PhpUnused
 *
 * @property-read Company|null $company
 * @property-read Contact|null $contact
 * @property-read Product|null $product
 * @property-read Collection<int, Activity> $activities
 * @property-read Collection<int, Activity> $activitiesAsCauser
 * @property-read Collection<int, Activity> $activitiesAsSubject
 */
#[Table('ledgers', key: 'id')]
#[Fillable([
    'company_id',
    'product_id',
    'contact_id',
    'document_number',
    'reference_number',
    'debit_acc',
    'credit_acc',
    'debit_acc_desc',
    'credit_acc_desc',
    'company_name',
    'product_name',
    'debit_id',
    'credit_id',
    'type',
    'subtype',
    'date',
    'due_date',
    'quantity',
    'unit_price',
    'warranty_months',
    'line_type',
    'subtotal_amount',
    'discount_id',
    'discount_name',
    'discount_type',
    'discount_value',
    'discount_rate',
    'discount_amount',
    'tax_id',
    'tax_name',
    'tax_rate',
    'tax_amount',
    'tax_mode',
    'line_total_amount',
    'acquisition_type',
    'debit_amount',
    'credit_amount',
    'total_amount',
    'attachment_path',
    'status',
    'notes',
    'payment_transfer_type',
    'description',
])]
class Ledger extends Model
{
    use HasActivity, HasUuids, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'id' => 'string',
            'company_id' => 'string',
            'product_id' => 'string',
            'contact_id' => 'string',
            'debit_id' => 'string',
            'credit_id' => 'string',
            'debit_acc' => 'string',
            'credit_acc' => 'string',
            'debit_acc_desc' => 'string',
            'credit_acc_desc' => 'string',
            'company_name' => 'string',
            'product_name' => 'string',
            'document_number' => 'string',
            'reference_number' => 'string',
            'type' => LedgerType::class,
            'subtype' => LedgerSubType::class,
            'purchase_type' => PurchaseOrderType::class,
            'date' => 'date',
            'due_date' => 'date',
            'quantity' => 'integer',
            'unit_price' => 'decimal:2',
            'warranty_months' => 'integer',
            'line_type' => LedgerLineType::class,
            'subtotal_amount' => 'decimal:2',
            'discount_id' => 'string',
            'discount_name' => 'string',
            'discount_type' => DiscountType::class,
            'discount_value' => 'decimal:4',
            'discount_rate' => 'decimal:4',
            'discount_amount' => 'decimal:2',
            'tax_id' => 'string',
            'tax_name' => 'string',
            'tax_rate' => 'decimal:4',
            'tax_amount' => 'decimal:2',
            'tax_mode' => TaxMode::class,
            'line_total_amount' => 'decimal:2',
            'acquisition_type' => AcquisitionType::class,
            'debit_amount' => 'decimal:2',
            'credit_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'attachment_path' => 'string',
            'status' => LedgerStatus::class,
            'notes' => 'string',
            'payment_transfer_type' => PaymentTransferType::class,
            'description' => 'string',
        ];
    }

    /**
     * Define an inverse one-to-one or many relationship.
     * Define an inverse one-to-one or many relationship with Company.
     *
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    /**
     * Define an inverse one-to-one or many relationship.
     * Define an inverse one-to-one or many relationship with Product.
     *
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }

    /**
     * Define an inverse one-to-one or many relationship.
     * Define an inverse one-to-one or many relationship with Contact.
     *
     * @return BelongsTo<Contact, $this>
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'contact_id', 'id');
    }

    /**
     * Get the options for logging activity using Spatie Activitylog.
     *
     * @return LogOptions Returns configured log options.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty();
    }
}
