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

use App\Models\Crm\CompanyAddress;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;

/**
 * One address a company can be reached or delivered at.
 *
 * A company can have several: a billing address, a warehouse, a branch. They are
 * separate records rather than columns on the company for that reason, and the
 * default flag says which one to use when nobody names one.
 *
 * The flag is a flag rather than a rule that forces exactly one default, because
 * unsetting it is a legitimate operation and a constraint that rejects the save
 * would make that impossible to express.
 *
 * All properties are nullable with defaults so the same object is built two ways:
 *
 *   CompanyAddressData::from($request->validated())   a form, to create or edit
 *   CompanyAddressData::fromModel($address)            a saved one, to read it
 */
class CompanyAddressData extends Data
{
    public function __construct(
        #[MapName('id')]
        public ?string $id = null,

        #[MapName('company_id')]
        public ?string $companyId = null,

        // What this address is for, so a list of them can be told apart.
        #[MapName('name')]
        public ?string $name = null,

        #[MapName('phone')]
        public ?string $phone = null,

        #[MapName('address')]
        public ?string $address = null,

        #[MapName('is_default')]
        public ?bool $isDefault = null,

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
     * @param  CompanyAddress  $model  The company address model instance.
     * @return self The populated DTO.
     */
    public static function fromModel(CompanyAddress $model): self
    {
        return new self(
            id: $model->id,
            companyId: $model->company_id,
            name: $model->name,
            phone: $model->phone,
            address: $model->address,
            isDefault: $model->is_default,
            createdAt: self::moment($model->created_at),
            updatedAt: self::moment($model->updated_at),
            deletedAt: self::moment($model->deleted_at),
        );
    }

    /**
     * The columns a company address is created from, and no others.
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
            'name' => $this->name,
            'phone' => $this->phone,
            'address' => $this->address,
            'is_default' => $this->isDefault === null ? null : (int) $this->isDefault,
        ];
    }

    /**
     * The row as the company_addresses table wants it.
     *
     * @return array<string, mixed>
     */
    public function toRow(): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->companyId,
            'name' => $this->name,
            'phone' => $this->phone,
            'address' => $this->address,
            'is_default' => $this->isDefault === null ? null : (int) $this->isDefault,
            'created_at' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt?->format('Y-m-d H:i:s'),
            'deleted_at' => $this->deletedAt?->format('Y-m-d H:i:s'),
        ];
    }
}
