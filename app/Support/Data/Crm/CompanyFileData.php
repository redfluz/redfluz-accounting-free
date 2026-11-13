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

namespace App\Support\Data\Crm;

use App\Models\Crm\CompanyFile;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;

/**
 * A file held against a company.
 *
 * Contracts, tax registrations and letters all live against the party rather than
 * against any one document, because one file can be referenced by several and
 * outlives the transaction that produced it. Attaching it to the company rather
 * than to an invoice is what keeps it findable after the invoice is archived.
 *
 * The name the file was uploaded under is kept beside the name it is stored
 * under, because the stored name has to be unique and machine-made while the one
 * the counterparty sent is neither. A reader needs the second; the filesystem
 * needs the first.
 *
 * All properties are nullable with defaults so the same object is built two ways:
 *
 *   CompanyFileData::from($request->validated())   a form, to create or edit
 *   CompanyFileData::fromModel($file)               a saved one, to read it
 */
class CompanyFileData extends Data
{
    public function __construct(
        #[MapName('id')]
        public ?string $id = null,

        #[MapName('company_id')]
        public ?string $companyId = null,

        #[MapName('title')]
        public ?string $title = null,

        #[MapName('notes')]
        public ?string $notes = null,

        #[MapName('remove_attachment')]
        public ?bool $removeAttachment = null,

        #[MapName('attachment')]
        public mixed $attachment = null,

        #[MapName('attachments')]
        public mixed $attachments = null,

        #[MapName('file_path')]
        public ?string $filePath = null,

        #[MapName('file_name')]
        public ?string $fileName = null,

        #[MapName('mime_type')]
        public ?string $mimeType = null,

        #[MapName('file_size')]
        public ?int $fileSize = null,

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
     * date is the string the database stores rather than the date object the model
     * holds, and the two are written in different formats. Taking the model's
     * attributes instead means the cast is what is mapped.
     *
     * @param  CompanyFile  $model  The company file model instance.
     * @return self The populated DTO.
     */
    public static function fromModel(CompanyFile $model): self
    {
        return new self(
            id: $model->id,
            companyId: $model->company_id,
            title: $model->title,
            notes: $model->notes,
            filePath: $model->file_path,
            fileName: $model->file_name,
            mimeType: $model->mime_type,
            fileSize: $model->file_size,
            createdAt: self::moment($model->created_at),
            updatedAt: self::moment($model->updated_at),
            deletedAt: self::moment($model->deleted_at),
        );
    }

    /**
     * The columns a company file is created from, and no others.
     *
     * Not toRow(): that writes all columns, which is right when a row is being
     * reproduced but wrong when one is being made.
     *
     * @return array<string, mixed>
     */
    public function toCreateRow(): array
    {
        return [
            'company_id' => $this->companyId,
            'title' => $this->title,
            'notes' => $this->notes,
            'file_path' => $this->filePath,
            'file_name' => $this->fileName,
            'mime_type' => $this->mimeType,
            'file_size' => $this->fileSize,
        ];
    }

    /**
     * The row as the company_files table wants it.
     *
     * @return array<string, mixed>
     */
    public function toRow(): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->companyId,
            'title' => $this->title,
            'notes' => $this->notes,
            'file_path' => $this->filePath,
            'file_name' => $this->fileName,
            'mime_type' => $this->mimeType,
            'file_size' => $this->fileSize,
            'created_at' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt?->format('Y-m-d H:i:s'),
            'deleted_at' => $this->deletedAt?->format('Y-m-d H:i:s'),
        ];
    }
}
