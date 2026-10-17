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

use App\Enums\LedgerStatus;
use App\Enums\LedgerSubType;
use App\Models\Sales\SalesAdjustment;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;

/**
 * A sales adjustment: a correction raised against an invoice that has gone out.
 *
 * The document and what it is made of are separate. This carries what is true of
 * the adjustment itself: which invoice it corrects, which way round it runs, who
 * it is for, and where it has got to. {@see SalesAdjustmentLineData} carries the
 * legs and the figures.
 *
 * The reason the adjustment exists is the reference number. Without it a
 * correction is a figure with no origin: there is no way to tell what it corrects,
 * so it cannot be applied to anything. It is therefore carried on the document
 * rather than on a line, where a note with several lines could point at more than
 * one invoice.
 *
 * The direction is a fact about the adjustment, not about a line. An increase
 * raises an invoice and a decrease reduces it, and the posting depends on which,
 * so it belongs on the header where it cannot differ between lines of the same
 * document.
 */
class SalesAdjustmentData extends Data
{
    public function __construct(
        #[MapName('id')]
        public ?string $id = null,

        #[MapName('document_number')]
        public ?string $adjustmentNumber = null,

        #[MapName('subtype')]
        public ?LedgerSubType $subtype = null,

        // The invoice this corrects. The reason the document exists.
        #[MapName('reference_number')]
        public ?string $referenceNumber = null,

        #[MapName('company_id')]
        public ?string $companyId = null,

        #[MapName('contact_id')]
        public ?string $contactId = null,

        #[MapName('company_name')]
        public ?string $companyName = null,

        #[MapName('contact_name')]
        public ?string $contactName = null,

        #[MapName('date')]
        public ?CarbonImmutable $date = null,

        #[MapName('status')]
        public ?LedgerStatus $status = null,

        #[MapName('attachment')]
        public mixed $attachment = null,

        #[MapName('attachment_path')]
        public ?string $attachmentPath = null,

        #[MapName('subtotal')]
        public ?string $subtotal = null,

        #[MapName('total_amount')]
        public ?string $totalAmount = null,

        #[MapName('quantity')]
        public ?string $quantity = null,

        #[MapName('unit_price')]
        public ?string $unitPrice = null,

        #[MapName('debit_id')]
        public ?string $debitId = null,

        #[MapName('credit_id')]
        public ?string $creditId = null,

        #[MapName('notes')]
        public ?string $notes = null,

        // Who decided the fate of this document. The name is a snapshot taken at
        // the moment of the decision, so the record survives that user being
        // renamed or removed.
        #[MapName('approved_by')]
        public ?string $approvedBy = null,

        #[MapName('approved_name')]
        public ?string $approvedName = null,

        #[MapName('approved_at')]
        public ?CarbonImmutable $approvedAt = null,

        #[MapName('cancelled_by')]
        public ?string $cancelledBy = null,

        #[MapName('cancelled_name')]
        public ?string $cancelledName = null,

        #[MapName('cancelled_at')]
        public ?CarbonImmutable $cancelledAt = null,

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
     * The columns a sales adjustment is created from, and no others.
     *
     * @return array<string, mixed>
     */
    public function toCreateRow(): array
    {
        return [
            'id' => $this->id,
            'document_number' => $this->adjustmentNumber,
            'subtype' => $this->subtype?->value,
            'reference_number' => $this->referenceNumber,
            'company_id' => $this->companyId,
            'contact_id' => $this->contactId,
            'company_name' => $this->companyName,
            'contact_name' => $this->contactName,
            'date' => $this->date?->format('Y-m-d'),
            'status' => $this->status->value ?? LedgerStatus::PENDING->value,
            'attachment_path' => $this->attachmentPath,
            'subtotal' => $this->subtotal,
            'total_amount' => $this->totalAmount,
            'notes' => $this->notes,
        ];
    }

    /**
     * Build from the model, which has already cast its own columns.
     *
     * Not Data::from($model->getAttributes()): that reads the raw row, where a
     * date is the string the database stores rather than the date object the
     * model holds, and the two are written in different formats. Taking the
     * model's attributes instead means the cast is what is mapped.
     */
    public static function fromModel(SalesAdjustment $model): self
    {
        return new self(
            id: $model->id,
            adjustmentNumber: $model->document_number,
            subtype: $model->subtype,
            referenceNumber: $model->reference_number,
            companyId: $model->company_id,
            contactId: $model->contact_id,
            companyName: $model->company_name,
            contactName: $model->contact_name,
            date: self::moment($model->date),
            status: $model->status,
            attachmentPath: $model->attachment_path,
            subtotal: $model->subtotal,
            totalAmount: $model->total_amount,
            notes: $model->notes,
            approvedBy: $model->approved_by,
            approvedName: $model->approved_name,
            approvedAt: self::moment($model->approved_at),
            cancelledBy: $model->cancelled_by,
            cancelledName: $model->cancelled_name,
            cancelledAt: self::moment($model->cancelled_at),
            createdAt: self::moment($model->created_at),
            updatedAt: self::moment($model->updated_at),
            deletedAt: self::moment($model->deleted_at),
        );
    }

    /**
     * The row as the sales_adjustments table wants it.
     *
     * @return array<string, mixed>
     */
    public function toRow(): array
    {
        return [
            'id' => $this->id,
            'document_number' => $this->adjustmentNumber,
            'subtype' => $this->subtype?->value,
            'reference_number' => $this->referenceNumber,
            'company_id' => $this->companyId,
            'contact_id' => $this->contactId,
            'company_name' => $this->companyName,
            'contact_name' => $this->contactName,
            'date' => $this->date?->format('Y-m-d'),
            'status' => $this->status?->value,
            'attachment_path' => $this->attachmentPath,
            'subtotal' => $this->subtotal,
            'total_amount' => $this->totalAmount,
            'notes' => $this->notes,
            'approved_by' => $this->approvedBy,
            'approved_name' => $this->approvedName,
            'approved_at' => $this->approvedAt?->format('Y-m-d H:i:s'),
            'cancelled_by' => $this->cancelledBy,
            'cancelled_name' => $this->cancelledName,
            'cancelled_at' => $this->cancelledAt?->format('Y-m-d H:i:s'),
            'created_at' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt?->format('Y-m-d H:i:s'),
            'deleted_at' => $this->deletedAt?->format('Y-m-d H:i:s'),
        ];
    }
}
