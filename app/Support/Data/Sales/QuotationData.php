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

use App\Enums\QuotationStatus;
use App\Models\Sales\Quotation;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;

/**
 * A quotation: an offer, before anything has been promised.
 *
 * This describes the document, not its lines. What a quotation is offering is
 * carried by {@see QuotationLineData}, and keeping the two apart is the point:
 * the header states a fact about the offer once, and a line states a fact about
 * one thing being offered.
 *
 * That separation is what the flat table could not do. Stored as one row per
 * side of the entry, it repeated expiry, status and the converted-to numbers on
 * every row, so a quotation with two lines had two copies of its own header that
 * could disagree, and counting quotations meant counting legs.
 *
 * It is built two ways and both are plain from():
 *
 *   QuotationData::from($request->validated())   a form, to raise one
 *   QuotationData::fromModel($quotation)          a saved one, to read it
 *
 * The properties the first of those cannot supply are optional, because a
 * quotation being raised has no number, no company name and no conversion yet.
 */
class QuotationData extends Data
{
    public function __construct(
        #[MapName('id')]
        public ?string $id = null,

        // ---- what the form supplies ----

        #[MapName('company_id')]
        public ?string $companyId = null,

        #[MapName('contact_id')]
        public ?string $contactId = null,

        #[MapName('reference_number')]
        public ?string $referenceNumber = null,

        #[MapName('date')]
        public ?CarbonImmutable $date = null,

        #[MapName('expiry_date')]
        public ?CarbonImmutable $expiryDate = null,

        #[MapName('status')]
        public ?QuotationStatus $status = null,

        #[MapName('notes')]
        public ?string $notes = null,

        #[MapName('attachment')]
        public mixed $attachment = null,

        // ---- what the caller supplies ----

        #[MapName('document_number')]
        public ?string $documentNumber = null,

        // ---- what the form cannot supply ----

        #[MapName('company_name')]
        public ?string $companyName = null,

        #[MapName('contact_name')]
        public ?string $contactName = null,

        #[MapName('attachment_path')]
        public ?string $attachmentPath = null,

        // What this quotation became. One place on the document rather than
        // copied onto every line, so a quotation cannot look converted on one
        // line and unconverted on another.
        #[MapName('is_converted')]
        public bool $isConverted = false,

        #[MapName('converted_at')]
        public ?CarbonImmutable $convertedAt = null,

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
     * A model hands back whatever Carbon its cast produced; this object holds one
     * concrete type so that a form and a model produce the same thing. The value
     * is the same either way, and only its class differs.
     */
    private static function moment(?CarbonInterface $value): ?CarbonImmutable
    {
        return $value === null ? null : CarbonImmutable::instance($value);
    }

    /**
     * The columns a quotation is created from, and no others.
     *
     * Not toRow(): that writes all columns, which is right when a row is being
     * reproduced but wrong when one is being made. Creating from the full row
     * would insert null for every column the model fills in for itself.
     *
     * @return array<string, mixed>
     */
    public function toCreateRow(): array
    {
        return [
            'id' => $this->id,
            'document_number' => $this->documentNumber,
            'company_id' => $this->companyId,
            'company_name' => $this->companyName,
            'contact_id' => $this->contactId,
            'contact_name' => $this->contactName,
            'reference_number' => $this->referenceNumber,
            'date' => $this->date?->format('Y-m-d'),
            'expiry_date' => $this->expiryDate?->format('Y-m-d'),
            'attachment_path' => $this->attachmentPath,
            'status' => $this->status->value ?? QuotationStatus::DRAFT->value,
            'is_converted' => 0,
            'notes' => $this->notes,
        ];
    }

    /**
     * The row as the quotations table wants it.
     *
     * @return array<string, mixed>
     */
    public function toRow(): array
    {
        return [
            'id' => $this->id,
            'document_number' => $this->documentNumber,
            'company_id' => $this->companyId,
            'company_name' => $this->companyName,
            'contact_id' => $this->contactId,
            'contact_name' => $this->contactName,
            'reference_number' => $this->referenceNumber,
            'date' => $this->date?->format('Y-m-d'),
            'expiry_date' => $this->expiryDate?->format('Y-m-d'),
            'attachment_path' => $this->attachmentPath,
            'status' => $this->status?->value,
            'is_converted' => (int) $this->isConverted,

            'converted_at' => $this->convertedAt?->format('Y-m-d H:i:s'),
            'notes' => $this->notes,
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
    public static function fromModel(Quotation $model): self
    {
        return new self(
            id: $model->id,
            companyId: $model->company_id,
            contactId: $model->contact_id,
            referenceNumber: $model->reference_number,
            date: self::moment($model->date),
            expiryDate: self::moment($model->expiry_date),
            status: $model->status,
            notes: $model->notes,
            documentNumber: $model->document_number,
            companyName: $model->company_name,
            contactName: $model->contact_name,
            attachmentPath: $model->attachment_path,
            isConverted: $model->is_converted,
            convertedAt: self::moment($model->converted_at),
            createdAt: self::moment($model->created_at),
            updatedAt: self::moment($model->updated_at),
            deletedAt: self::moment($model->deleted_at),
        );
    }
}
