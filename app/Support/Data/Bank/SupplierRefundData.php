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

namespace App\Support\Data\Bank;

use App\Enums\LedgerStatus;
use App\Enums\PaymentTransferType;
use App\Models\Bank\Refund\SupplierRefund;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;

/**
 * A customer refund: money given back to a supplier who was paid.
 *
 * This object holds the data and nothing else. It does not know it came from a
 * request, it does not look anything up, and it does not decide anything. A
 * controller builds one from whatever it is holding and then works from the
 * object, which is why the field names stop appearing in the controller.
 *
 * It is built two ways and both are plain from():
 *
 *   SupplierRefundData::from($request->validated())   a form, to raise one
 *   SupplierRefundData::fromModel($refund)             a saved one, to read it
 *
 * The properties the first of those cannot supply are optional, because a refund
 * being raised has no approver, no cancellation and no key yet. It is filled in
 * by whoever creates the row, and Data::from leaves it null rather than inventing
 * a value.
 *
 * What the document is made of is carried by {@see SupplierRefundLineData}, one
 * entry per side of the figure. The account ids are held here as well, because
 * they arrive on the form with the rest and the controller needs them to write
 * those lines; they are not columns of this table and toCreateRow leaves them out.
 *
 * The reference number is the reason the document exists. It names the receipt,
 * invoice or payment being received, paid or undone, and without it the figure is
 * money moving with no origin and nothing to settle against.
 *
 * The decision columns are kept as an id and a name together, so the record of who
 * approved or cancelled the document survives that user being renamed or removed.
 */
class SupplierRefundData extends Data
{
    public function __construct(
        #[MapName('id')]
        public ?string $id = null,

        // ---- what the form supplies ----

        #[MapName('company_id')]
        public ?string $companyId = null,

        #[MapName('document_date')]
        public ?CarbonImmutable $date = null,

        #[MapName('reference_number')]
        public ?string $referenceNumber = null,

        // The form says "amount". The column says "total_amount", because on a
        // document the amount is the total. Mapping the property to the form's
        // name and letting toCreateRow write the column's name keeps each honest
        // instead of making the form field a second thing called amount.
        #[MapName('amount')]
        public ?string $totalAmount = null,

        #[MapName('notes')]
        public ?string $notes = null,

        #[MapName('payment_transfer_type')]
        public ?PaymentTransferType $paymentTransferType = null,

        // The two accounts the figure is booked between. They belong to the lines
        // rather than to this table, but they arrive on the same form.
        #[MapName('debit_id')]
        public ?string $debitAccountId = null,

        #[MapName('credit_id')]
        public ?string $creditAccountId = null,

        #[MapName('attachment')]
        public mixed $attachment = null,

        // ---- what the caller supplies ----

        #[MapName('document_number')]
        public ?string $documentNumber = null,

        // ---- what the form cannot supply ----

        #[MapName('status')]
        public LedgerStatus $status = LedgerStatus::PENDING,

        #[MapName('company_name')]
        public ?string $companyName = null,

        #[MapName('contact_id')]
        public ?string $contactId = null,

        #[MapName('contact_name')]
        public ?string $contactName = null,

        public ?string $currency = null,

        #[MapName('attachment_path')]
        public ?string $attachmentPath = null,

        #[MapName('total_amount')]
        public ?string $storedTotalAmount = null,

        #[MapName('created_by')]
        public ?string $createdBy = null,

        #[MapName('created_name')]
        public ?string $createdName = null,

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

        #[MapName('undone_by')]
        public ?string $undoneBy = null,

        #[MapName('undone_name')]
        public ?string $undoneName = null,

        #[MapName('undone_at')]
        public ?CarbonImmutable $undoneAt = null,

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
     * The amount the document is for, whichever way it arrived.
     *
     * The form calls it amount and the column calls it total_amount, and this is
     * the one place that has to know both. Reading it from either name means a
     * caller does not have to remember which object it built.
     */
    public function amount(): string
    {
        return $this->storedTotalAmount ?? $this->totalAmount ?? '0.00';
    }

    /**
     * Build from the model, which has already cast its own columns.
     *
     * Not Data::from($model->getAttributes()): that reads the raw row, where a
     * date is the string the database stores rather than the date object the model
     * holds, and the two are written in different formats. Taking the model's
     * attributes instead means the cast is what is mapped.
     */
    public static function fromModel(SupplierRefund $model): self
    {
        return new self(
            id: $model->id,
            companyId: $model->company_id,
            date: CarbonImmutable::instance($model->date),
            referenceNumber: $model->reference_number,
            notes: $model->notes,
            paymentTransferType: $model->payment_transfer_type,
            documentNumber: $model->document_number,
            status: $model->status,
            companyName: $model->company_name,
            contactId: $model->contact_id,
            contactName: $model->contact_name,
            attachmentPath: $model->attachment_path,
            storedTotalAmount: $model->total_amount,
            createdBy: $model->created_by,
            createdName: $model->created_name,
            approvedBy: $model->approved_by,
            approvedName: $model->approved_name,
            approvedAt: self::moment($model->approved_at),
            cancelledBy: $model->cancelled_by,
            cancelledName: $model->cancelled_name,
            cancelledAt: self::moment($model->cancelled_at),
            undoneBy: $model->undone_by,
            undoneName: $model->undone_name,
            undoneAt: self::moment($model->undone_at),
            createdAt: self::moment($model->created_at),
            updatedAt: self::moment($model->updated_at),
            deletedAt: self::moment($model->deleted_at),
        );
    }

    /**
     * The columns a refund is created from, and no others.
     *
     * Not toRow(): that writes all thirty columns, which is right when a row is
     * being reproduced but wrong when one is being made. Creating from the full
     * row would insert null for created_by, approved_by and every other column the
     * model fills in for itself, and the refund would be written already
     * unattributed.
     *
     * So the creation columns are named one by one, and the columns the model owns
     * are simply absent from the array. What is left out is not forgotten; it is
     * the part that had not happened yet when this document was raised.
     *
     * The two account ids are absent because they are not columns of this table.
     * They become the document's lines, which the controller writes.
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
            'date' => $this->date?->format('Y-m-d'),
            'status' => $this->status->value,
            'payment_transfer_type' => $this->paymentTransferType?->value,
            'reference_number' => $this->referenceNumber,
            'attachment_path' => $this->attachmentPath,
            'notes' => $this->notes,
            'total_amount' => $this->amount(),
        ];
    }

    /**
     * The row as the customer_refunds table wants it.
     *
     * @return array<string, mixed>
     */
    public function toRow(): array
    {
        return [
            'id' => $this->id,
            'document_number' => $this->documentNumber,
            'company_id' => $this->companyId,
            'contact_id' => $this->contactId,
            'company_name' => $this->companyName,
            'contact_name' => $this->contactName,
            'date' => $this->date?->format('Y-m-d'),
            'status' => $this->status->value,
            'payment_transfer_type' => $this->paymentTransferType?->value,
            'reference_number' => $this->referenceNumber,
            'attachment_path' => $this->attachmentPath,
            'notes' => $this->notes,
            'total_amount' => $this->amount(),
            'created_by' => $this->createdBy,
            'created_name' => $this->createdName,
            'approved_by' => $this->approvedBy,
            'approved_name' => $this->approvedName,
            'approved_at' => $this->approvedAt?->format('Y-m-d H:i:s'),
            'cancelled_by' => $this->cancelledBy,
            'cancelled_name' => $this->cancelledName,
            'cancelled_at' => $this->cancelledAt?->format('Y-m-d H:i:s'),
            'undone_by' => $this->undoneBy,
            'undone_name' => $this->undoneName,
            'undone_at' => $this->undoneAt?->format('Y-m-d H:i:s'),
            'created_at' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt?->format('Y-m-d H:i:s'),
            'deleted_at' => $this->deletedAt?->format('Y-m-d H:i:s'),
        ];
    }
}
