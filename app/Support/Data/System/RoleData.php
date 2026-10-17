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

namespace App\Support\Data\System;

use App\Enums\DashboardRole;
use App\Models\System\Role;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;

/**
 * A role: a named set of permissions assigned to users.
 *
 * This object holds the data and nothing else. It does not know it came from a
 * request, it does not look anything up, and it does not decide anything. A
 * controller builds one from whatever it is holding and then works from the
 * object.
 *
 * It is built two ways:
 *
 *   RoleData::from($request->validated())   a form, to create one
 *   RoleData::fromModel($role)              a saved one, to read it
 */
class RoleData extends Data
{
    public function __construct(
        #[MapName('id')]
        public ?string $id = null,

        #[MapName('name')]
        public ?string $name = null,

        #[MapName('dashboard')]
        public ?DashboardRole $dashboard = null,

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
     * @param  Role  $model  The role model instance.
     * @return self The populated data object.
     */
    public static function fromModel(Role $model): self
    {
        return new self(
            id: $model->id,
            name: $model->name,
            dashboard: $model->dashboard,
            isDefault: $model->is_default,
            createdAt: self::moment($model->created_at),
            updatedAt: self::moment($model->updated_at),
            deletedAt: self::moment($model->deleted_at),
        );
    }

    /**
     * The columns a role is created from, and no others.
     *
     * @return array<string, mixed>
     */
    public function toCreateRow(): array
    {
        return [
            'name' => $this->name,
            'dashboard' => $this->dashboard,
        ];
    }

    /**
     * The row as the roles table wants it.
     *
     * @return array<string, mixed>
     */
    public function toRow(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'dashboard' => $this->dashboard?->value,
            'is_default' => $this->isDefault,
            'created_at' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt?->format('Y-m-d H:i:s'),
            'deleted_at' => $this->deletedAt?->format('Y-m-d H:i:s'),
        ];
    }
}
