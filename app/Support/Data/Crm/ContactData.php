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

use App\Models\Crm\Contact;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;

/**
 * A person at a company the business trades with.
 *
 * A contact belongs to a company and inherits its position in the ledger: the
 * money is owed by the company, not by the person. Carrying the company on the
 * contact is what lets a document name a person while the balance stays with the
 * party that is actually responsible for it.
 *
 * All properties are nullable with defaults so the same object is built two ways:
 *
 *   ContactData::from($request->validated())   a form, to create or edit
 *   ContactData::fromModel($contact)            a saved one, to read it
 */
class ContactData extends Data
{
    public function __construct(
        #[MapName('id')]
        public ?string $id = null,

        #[MapName('company_id')]
        public ?string $companyId = null,

        #[MapName('name')]
        public ?string $name = null,

        #[MapName('email')]
        public ?string $email = null,

        #[MapName('phone')]
        public ?string $phone = null,

        // What this person does at the company. Recorded because "accounts payable"
        // tells a reader who to ask about an overdue figure and a job title does
        // not.
        #[MapName('position')]
        public ?string $position = null,

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
     * @param  Contact  $model  The contact model instance.
     * @return self The populated DTO.
     */
    public static function fromModel(Contact $model): self
    {
        return new self(
            id: $model->id,
            companyId: $model->company_id,
            name: $model->name,
            email: $model->email,
            phone: $model->phone,
            position: $model->position,
            createdAt: self::moment($model->created_at),
            updatedAt: self::moment($model->updated_at),
            deletedAt: self::moment($model->deleted_at),
        );
    }

    /**
     * The columns a contact is created from, and no others.
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
            'email' => $this->email,
            'phone' => $this->phone,
            'position' => $this->position,
        ];
    }

    /**
     * The row as the contacts table wants it.
     *
     * @return array<string, mixed>
     */
    public function toRow(): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->companyId,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'position' => $this->position,
            'created_at' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt?->format('Y-m-d H:i:s'),
            'deleted_at' => $this->deletedAt?->format('Y-m-d H:i:s'),
        ];
    }
}
