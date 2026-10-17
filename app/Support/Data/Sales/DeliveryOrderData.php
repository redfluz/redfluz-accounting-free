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

use App\Enums\DeliveryOrderStatus;
use App\Enums\ShippingStatus;
use App\Models\Sales\DeliveryOrder;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;

/**
 * A delivery order: goods leaving the premises against a sales order.
 *
 * What left is carried by {@see DeliveryOrderLineData}. This carries what is true
 * of the delivery as a whole: which order it is fulfilling, where it is going, who
 * receives it, and how far it has got.
 *
 * The stock location is snapshotted by name as well as id, because a delivery note
 * is read on its own and a reader needs to know where the goods came from without
 * opening the location table. The same reasoning as the account names on a
 * quotation line: the name is what a person reads, and it is taken now rather
 * than resolved later.
 *
 * It is built two ways and both are plain from():
 *
 *   DeliveryOrderData::from($request->validated())   a form, to raise one
 *   DeliveryOrderData::fromModel($deliveryOrder)      a saved one, to read it
 *
 * The properties the first of those cannot supply are optional, because a
 * delivery order being raised has no number, no stock location name and no
 * posting yet.
 */
class DeliveryOrderData extends Data
{
    public function __construct(
        #[MapName('id')]
        public ?string $id = null,

        // ---- what the form supplies ----

        #[MapName('sales_order_id')]
        public ?string $salesOrderId = null,

        #[MapName('company_address_id')]
        public ?string $companyAddressId = null,

        #[MapName('stock_location_id')]
        public ?string $stockLocationId = null,

        #[MapName('delivery_date')]
        public ?CarbonImmutable $deliveryDate = null,

        #[MapName('recipient_name')]
        public ?string $recipientName = null,

        #[MapName('recipient_phone')]
        public ?string $recipientPhone = null,

        #[MapName('delivery_address')]
        public ?string $deliveryAddress = null,

        #[MapName('courier_company_id')]
        public ?string $courierCompanyId = null,

        #[MapName('tracking_number')]
        public ?string $trackingNumber = null,

        #[MapName('shipping_date')]
        public ?CarbonImmutable $shippingDate = null,

        #[MapName('shipping_status')]
        public ?ShippingStatus $shippingStatus = null,

        #[MapName('attachment')]
        public mixed $attachment = null,

        #[MapName('notes')]
        public ?string $notes = null,

        // ---- what the caller supplies ----

        #[MapName('document_number')]
        public ?string $documentNumber = null,

        #[MapName('stock_location_name')]
        public ?string $stockLocationName = null,

        #[MapName('courier')]
        public ?string $courier = null,

        #[MapName('status')]
        public ?DeliveryOrderStatus $status = null,

        #[MapName('attachment_path')]
        public ?string $attachmentPath = null,

        #[MapName('posted_by')]
        public ?string $postedBy = null,

        #[MapName('posted_at')]
        public ?CarbonImmutable $postedAt = null,

        #[MapName('created_at')]
        public ?CarbonImmutable $createdAt = null,

        #[MapName('updated_at')]
        public ?CarbonImmutable $updatedAt = null,

        #[MapName('deleted_at')]
        public ?CarbonImmutable $deletedAt = null,
    ) {}

    /**
     * The same moment, as the type this object holds.
     *
     * @param  CarbonInterface|null  $value  The moment to normalise.
     * @return CarbonImmutable|null The moment as an immutable, or null.
     */
    private static function moment(?CarbonInterface $value): ?CarbonImmutable
    {
        return $value === null ? null : CarbonImmutable::instance($value);
    }

    /**
     * The columns a delivery order is created from, and no others.
     *
     * @return array<string, mixed>
     */
    public function toCreateRow(): array
    {
        return [
            'id' => $this->id,
            'document_number' => $this->documentNumber,
            'sales_order_id' => $this->salesOrderId,
            'company_address_id' => $this->companyAddressId,
            'stock_location_id' => $this->stockLocationId,
            'stock_location_name' => $this->stockLocationName,
            'delivery_date' => $this->deliveryDate?->format('Y-m-d'),
            'recipient_name' => $this->recipientName,
            'recipient_phone' => $this->recipientPhone,
            'delivery_address' => $this->deliveryAddress,
            'courier_company_id' => $this->courierCompanyId,
            'courier' => $this->courier,
            'tracking_number' => $this->trackingNumber,
            'shipping_date' => $this->shippingDate?->format('Y-m-d'),
            'shipping_status' => $this->shippingStatus?->value,
            'status' => $this->status->value ?? DeliveryOrderStatus::DRAFT->value,
            'attachment_path' => $this->attachmentPath,
            'notes' => $this->notes,
        ];
    }

    /**
     * The row as the delivery_orders table wants it.
     *
     * @return array<string, mixed>
     */
    public function toRow(): array
    {
        return [
            'id' => $this->id,
            'document_number' => $this->documentNumber,
            'sales_order_id' => $this->salesOrderId,
            'company_address_id' => $this->companyAddressId,
            'stock_location_id' => $this->stockLocationId,
            'stock_location_name' => $this->stockLocationName,
            'delivery_date' => $this->deliveryDate?->format('Y-m-d'),
            'recipient_name' => $this->recipientName,
            'recipient_phone' => $this->recipientPhone,
            'delivery_address' => $this->deliveryAddress,
            'courier_company_id' => $this->courierCompanyId,
            'courier' => $this->courier,
            'tracking_number' => $this->trackingNumber,
            'shipping_date' => $this->shippingDate?->format('Y-m-d'),
            'shipping_status' => $this->shippingStatus?->value,
            'status' => $this->status?->value,
            'attachment_path' => $this->attachmentPath,
            'notes' => $this->notes,
            'posted_by' => $this->postedBy,
            'posted_at' => $this->postedAt?->format('Y-m-d H:i:s'),
            'created_at' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt?->format('Y-m-d H:i:s'),
            'deleted_at' => $this->deletedAt?->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Build from the model, which has already cast its own columns.
     *
     * Not Data::from($model->getAttributes()): that reads the raw row, where a
     * date is the string the database stores rather than the date object the
     * model holds, and the two are written in different formats. Taking the
     * model's attributes instead means the cast is what is mapped, so a column
     * cast to a date, an enum or a decimal is mapped as the type it actually is.
     */
    public static function fromModel(DeliveryOrder $model): self
    {
        return new self(
            id: $model->id,
            salesOrderId: $model->sales_order_id,
            companyAddressId: $model->company_address_id,
            stockLocationId: $model->stock_location_id,
            deliveryDate: self::moment($model->delivery_date),
            recipientName: $model->recipient_name,
            recipientPhone: $model->recipient_phone,
            deliveryAddress: $model->delivery_address,
            courierCompanyId: $model->courier_company_id,
            trackingNumber: $model->tracking_number,
            shippingDate: self::moment($model->shipping_date),
            shippingStatus: $model->shipping_status,
            notes: $model->notes,
            documentNumber: $model->document_number,
            stockLocationName: $model->stock_location_name,
            courier: $model->courier,
            status: $model->status,
            attachmentPath: $model->attachment_path,
            postedBy: $model->posted_by,
            postedAt: self::moment($model->posted_at),
            createdAt: self::moment($model->created_at),
            updatedAt: self::moment($model->updated_at),
            deletedAt: self::moment($model->deleted_at),
        );
    }
}
