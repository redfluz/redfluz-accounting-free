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

namespace App\Support\Data\Inventory;

use App\Models\Inventory\GoodsReceiptAttachment;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;

/**
 * A file held against a goods receipt.

    Attached to the document number rather than to a receipt id, because the
    receipt is the thing that carries the number a supplier and a reader both use,
    and the file has to be findable by that number alone.
 */
class GoodsReceiptAttachmentData extends Data
{
    public function __construct(
        #[MapName('id')]
        public string $id,

        #[MapName('document_number')]
        public ?string $documentNumber,

        #[MapName('file_path')]
        public ?string $filePath,

        #[MapName('original_name')]
        public ?string $originalName,

        #[MapName('mime_type')]
        public ?string $mimeType,

        #[MapName('file_size')]
        public ?int $fileSize,

        #[MapName('uploaded_by')]
        public ?string $uploadedBy,

        #[MapName('uploaded_by_name')]
        public ?string $uploadedByName,

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
     * date is the string the database stores rather than the date object the model
     * holds, and the two are written in different formats. Taking the model's
     * attributes instead means the cast is what is mapped.
     */
    public static function fromModel(GoodsReceiptAttachment $model): self
    {
        return new self(
            id: $model->id,
            documentNumber: $model->document_number,
            filePath: $model->file_path,
            originalName: $model->original_name,
            mimeType: $model->mime_type,
            fileSize: $model->file_size,
            uploadedBy: $model->uploaded_by,
            uploadedByName: $model->uploaded_by_name,
            createdAt: self::moment($model->created_at),
            updatedAt: self::moment($model->updated_at),
            deletedAt: self::moment($model->deleted_at),
        );
    }

    /**
     * The row as the goods_receipt_attachments table wants it.
     *
     * @return array<string, mixed>
     */
    public function toRow(): array
    {
        /** @var array<string, mixed> $row */
        $row = [
            ...$this->toArray(),
            'created_at' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt?->format('Y-m-d H:i:s'),
            'deleted_at' => $this->deletedAt?->format('Y-m-d H:i:s'),
        ];

        return $row;
    }
}
