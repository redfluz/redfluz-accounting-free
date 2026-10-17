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

namespace App\Support\Data\Purchase;

use App\Enums\PurchaseOrderType;
use App\Models\Purchase\PurchaseInvoice;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;

/**
 * A purchase invoice: goods received from a supplier, to be paid.
 *
 * It is built two ways and both are plain from():
 *
 *   PurchaseInvoiceData::from($request->validated())   a form, to raise one
 *   PurchaseInvoiceData::fromModel($entry)              a saved one, to read it
 *
 * The properties the first of those cannot supply are optional, because an invoice
 * being raised has no approver, no key yet. It is filled in by whoever creates the
 * row, and Data::from leaves it null rather than inventing a value.
 */
class PurchaseInvoiceData extends Data
{
    public function __construct(
        #[MapName('id')]
        public ?string $id = null,

        #[MapName('company_id')]
        public ?string $companyId = null,

        #[MapName('contact_id')]
        public ?string $contactId = null,

        #[MapName('debit_id')]
        public ?string $debitId = null,

        #[MapName('credit_id')]
        public ?string $creditId = null,

        #[MapName('date')]
        public ?CarbonImmutable $date = null,

        #[MapName('purchase_type')]
        public ?PurchaseOrderType $purchaseType = null,

        #[MapName('credit_term_days')]
        public ?int $creditTermDays = null,

        #[MapName('due_date')]
        public ?CarbonImmutable $dueDate = null,

        #[MapName('amount')]
        public ?string $amount = null,

        #[MapName('payment_debit_id')]
        public ?string $paymentDebitId = null,

        #[MapName('payment_credit_id')]
        public ?string $paymentCreditId = null,

        #[MapName('invoice_notes')]
        public ?string $invoiceNotes = null,

        #[MapName('payment_notes')]
        public ?string $paymentNotes = null,

        #[MapName('document_number')]
        public ?string $documentNumber = null,

        #[MapName('reference_number')]
        public ?string $referenceNumber = null,

        #[MapName('company_name')]
        public ?string $companyName = null,

        #[MapName('attachment_path')]
        public ?string $attachmentPath = null,

        #[MapName('invoice_attachment')]
        public mixed $attachment = null,

        #[MapName('status')]
        public ?string $status = null,

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
     * Build from the model, which has already cast its own columns.
     *
     * Not Data::from($model->getAttributes()): that reads the raw row, where a
     * date is the string the database stores rather than the date object the
     * model holds, and the two are written in different formats. Taking the
     * model's attributes instead means the cast is what is mapped.
     */
    /**
     * Read a saved document back into this object.
     *
     * @param  PurchaseInvoice  $model  a saved purchase invoice.
     */
    public static function fromModel(PurchaseInvoice $model): self
    {
        return new self(
            id: $model->id,
            referenceNumber: $model->reference_number,
            companyId: $model->company_id,
            companyName: $model->company_name,
            amount: $model->total_amount,
            attachmentPath: $model->attachment_path,
            createdAt: self::moment($model->created_at),
            updatedAt: self::moment($model->updated_at),
            deletedAt: self::moment($model->deleted_at),
        );
    }

    /**
     * The columns a purchase invoice is created from, and no others.
     *
     * Not toRow(): that writes all columns, which is right when a row is being
     * reproduced but wrong when one is being made. Creating from the full row
     * would insert null for every column the model fills in for itself, and the
     * invoice would be written already unattributed.
     *
     * @return array<string, mixed>
     */
    public function toCreateRow(): array
    {
        return [
            'company_id' => $this->companyId,
            'contact_id' => $this->contactId,
            'debit_id' => $this->debitId,
            'credit_id' => $this->creditId,
            'purchase_type' => $this->purchaseType?->value,
            'date' => $this->date?->format('Y-m-d'),
            'credit_term_days' => $this->creditTermDays,
            'due_date' => $this->dueDate?->format('Y-m-d'),
            'document_number' => $this->documentNumber,
            'reference_number' => $this->referenceNumber,
            'company_name' => $this->companyName,
            'attachment_path' => $this->attachmentPath,
            'notes' => $this->invoiceNotes,
        ];
    }

    /**
     * The row as the ap_ledgers table wants it.
     *
     * @return array<string, mixed>
     */
    public function toRow(): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->companyId,
            'contact_id' => $this->contactId,
            'debit_id' => $this->debitId,
            'credit_id' => $this->creditId,
            'purchase_type' => $this->purchaseType?->value,
            'date' => $this->date?->format('Y-m-d'),
            'due_date' => $this->dueDate?->format('Y-m-d'),
            'document_number' => $this->documentNumber,
            'reference_number' => $this->referenceNumber,
            'company_name' => $this->companyName,
            'attachment_path' => $this->attachmentPath,
            'status' => $this->status,
            'notes' => $this->invoiceNotes,
            'created_at' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt?->format('Y-m-d H:i:s'),
            'deleted_at' => $this->deletedAt?->format('Y-m-d H:i:s'),
        ];
    }
}
