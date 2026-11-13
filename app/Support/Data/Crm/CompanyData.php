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

use App\Models\Crm\Company;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;

/**
 * A company the business trades with.
 *
 * The three role flags are kept as three columns rather than one role, because a
 * party is not one thing. A business can be both a debtor it is owed by and a
 * creditor it owes, and both figures then belong on its balance. A single role
 * column would make that unrepresentable and force the two positions apart.
 *
 * The address here is the company's own address. The addresses it is invoiced and
 * delivered to are separate records, because a company can be billed at one place
 * and shipped from another, and collapsing them would lose which is which.
 *
 * All properties are nullable with defaults so the same object is built two ways:
 *
 *   CompanyData::from($request->validated())   a form, to create or edit
 *   CompanyData::fromModel($company)            a saved one, to read it
 */
class CompanyData extends Data
{
    public function __construct(
        #[MapName('id')]
        public ?string $id = null,

        #[MapName('name')]
        public ?string $name = null,

        #[MapName('email')]
        public ?string $email = null,

        #[MapName('phone')]
        public ?string $phone = null,

        #[MapName('address')]
        public ?string $address = null,

        #[MapName('is_debtor')]
        public ?bool $isDebtor = null,

        #[MapName('is_creditor')]
        public ?bool $isCreditor = null,

        #[MapName('is_courier')]
        public ?bool $isCourier = null,

        #[MapName('logo')]
        public mixed $logo = null,

        #[MapName('logo_path')]
        public ?string $logoPath = null,

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
     * @param  Company  $model  The company model instance.
     * @return self The populated DTO.
     */
    public static function fromModel(Company $model): self
    {
        return new self(
            id: $model->id,
            name: $model->name,
            email: $model->email,
            phone: $model->phone,
            address: $model->address,
            isDebtor: $model->is_debtor,
            isCreditor: $model->is_creditor,
            isCourier: $model->is_courier,
            logoPath: $model->logo_path,
            createdAt: self::moment($model->created_at),
            updatedAt: self::moment($model->updated_at),
            deletedAt: self::moment($model->deleted_at),
        );
    }

    /**
     * The columns a company is created from, and no others.
     *
     * Not toRow(): that writes all columns, which is right when a row is being
     * reproduced but wrong when one is being made. Creating from the full row
     * would insert null for created_at and every other column the model fills in
     * for itself.
     *
     * @return array<string, mixed>
     */
    public function toCreateRow(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'is_debtor' => $this->isDebtor === null ? null : (int) $this->isDebtor,
            'is_creditor' => $this->isCreditor === null ? null : (int) $this->isCreditor,
            'is_courier' => $this->isCourier === null ? null : (int) $this->isCourier,
            'logo_path' => $this->logoPath,
        ];
    }

    /**
     * The row as the companies table wants it.
     *
     * @return array<string, mixed>
     */
    public function toRow(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'is_debtor' => $this->isDebtor === null ? null : (int) $this->isDebtor,
            'is_creditor' => $this->isCreditor === null ? null : (int) $this->isCreditor,
            'is_courier' => $this->isCourier === null ? null : (int) $this->isCourier,
            'logo_path' => $this->logoPath,
            'created_at' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt?->format('Y-m-d H:i:s'),
            'deleted_at' => $this->deletedAt?->format('Y-m-d H:i:s'),
        ];
    }
}
