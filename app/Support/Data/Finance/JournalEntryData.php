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

namespace App\Support\Data\Finance;

use App\Enums\LedgerStatus;
use App\Models\Finance\JournalEntry;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;

/**
 * A journal entry: a manual double-entry posting to the general ledger.
 *
 * A journal entry is the only way to adjust the ledger outside a source
 * document. It carries a date, a reference number, notes and a set of lines
 * that must balance. The entry number is assigned on creation; the status
 * decides whether the lines have been posted yet.
 *
 * The lines themselves are held by {@see JournalEntryChart} and are not part of
 * this object: the controller builds them from the validated items array and
 * writes them separately, because they are rows of a different table.
 */
class JournalEntryData extends Data
{
    public function __construct(
        #[MapName('id')]
        public ?string $id = null,

        #[MapName('date')]
        public ?CarbonImmutable $date = null,

        #[MapName('reference_number')]
        public ?string $referenceNumber = null,

        #[MapName('notes')]
        public ?string $notes = null,

        #[MapName('document_number')]
        public ?string $entryNumber = null,

        #[MapName('total_debit')]
        public ?string $totalDebit = null,

        #[MapName('total_credit')]
        public ?string $totalCredit = null,

        #[MapName('status')]
        public ?LedgerStatus $status = null,

        #[MapName('reversal_of_id')]
        public ?string $reversalOfId = null,

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
     *
     * @param  CarbonInterface|null  $value  The carbon instance to convert.
     * @return CarbonImmutable|null The converted immutable carbon instance.
     */
    private static function moment(?CarbonInterface $value): ?CarbonImmutable
    {
        return $value === null ? null : CarbonImmutable::instance($value);
    }

    /**
     * Build from the model, which has already cast its own columns.
     *
     * Not Data::from($model->getAttributes()): that reads the raw row, where a
     * date is the string the database stores rather than the date object the model
     * holds, and the two are written in different formats. Taking the model's
     * attributes instead means the cast is what is mapped.
     *
     * @param  JournalEntry  $model  The journal entry model instance.
     * @return self The populated data object.
     */
    public static function fromModel(JournalEntry $model): self
    {
        return new self(
            id: $model->id,
            date: self::moment($model->date),
            referenceNumber: $model->reference_number,
            notes: $model->notes,
            entryNumber: $model->document_number,
            totalDebit: $model->total_debit,
            totalCredit: $model->total_credit,
            status: $model->status,
            reversalOfId: $model->reversal_of_id,
            createdAt: self::moment($model->created_at),
            updatedAt: self::moment($model->updated_at),
            deletedAt: self::moment($model->deleted_at),
        );
    }

    /**
     * The columns a journal entry is created from, and no others.
     *
     * Not toRow(): that writes all columns, which is right when a row is being
     * reproduced but wrong when one is being made. Creating from the full row
     * would insert null for timestamps and status the model fills in for itself.
     *
     * @return array<string, mixed>
     */
    public function toCreateRow(): array
    {
        return [
            'document_number' => $this->entryNumber,
            'reference_number' => $this->referenceNumber,
            'date' => $this->date?->format('Y-m-d'),
            'total_debit' => $this->totalDebit,
            'total_credit' => $this->totalCredit,
            'status' => $this->status?->value,
            'notes' => $this->notes,
        ];
    }

    /**
     * The row as the journal_entries table wants it.
     *
     * @return array<string, mixed>
     */
    public function toRow(): array
    {
        return [
            'id' => $this->id,
            'reversal_of_id' => $this->reversalOfId,
            'document_number' => $this->entryNumber,
            'reference_number' => $this->referenceNumber,
            'date' => $this->date?->format('Y-m-d'),
            'total_debit' => $this->totalDebit,
            'total_credit' => $this->totalCredit,
            'status' => $this->status?->value,
            'notes' => $this->notes,
            'created_at' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt?->format('Y-m-d H:i:s'),
            'deleted_at' => $this->deletedAt?->format('Y-m-d H:i:s'),
        ];
    }
}
