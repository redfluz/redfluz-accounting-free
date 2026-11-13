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

use App\Models\System\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;

/**
 * A user: an authenticated account with a name, email and role.
 *
 * This object holds the data and nothing else. It does not know it came from a
 * request, it does not look anything up, and it does not decide anything. A
 * controller builds one from whatever it is holding and then works from the
 * object.
 *
 * It is built two ways:
 *
 *   UserData::from($request->validated())   a form, to create one
 *   UserData::fromModel($user)              a saved one, to read it
 */
class UserData extends Data
{
    public function __construct(
        #[MapName('id')]
        public ?string $id = null,

        #[MapName('role_id')]
        public ?string $roleId = null,

        #[MapName('name')]
        public ?string $name = null,

        #[MapName('email')]
        public ?string $email = null,

        #[MapName('password')]
        public ?string $password = null,

        #[MapName('must_change_password')]
        public ?bool $mustChangePassword = null,

        #[MapName('locale')]
        public ?string $locale = null,

        #[MapName('picture')]
        public ?string $picture = null,

        #[MapName('is_employee')]
        public ?bool $isEmployee = null,

        #[MapName('email_verified_at')]
        public ?CarbonImmutable $emailVerifiedAt = null,

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
     * @param  User  $model  The user model instance.
     * @return self The populated data object.
     */
    public static function fromModel(User $model): self
    {
        return new self(
            id: $model->id,
            roleId: $model->role_id,
            name: $model->name,
            email: $model->email,
            mustChangePassword: $model->must_change_password,
            locale: $model->locale,
            picture: $model->picture,
            isEmployee: $model->is_employee,
            emailVerifiedAt: self::moment($model->email_verified_at),
            createdAt: self::moment($model->created_at),
            updatedAt: self::moment($model->updated_at),
            deletedAt: self::moment($model->deleted_at),
        );
    }

    /**
     * The columns a user is created from, and no others.
     *
     * @return array<string, mixed>
     */
    public function toCreateRow(): array
    {
        return [
            'role_id' => $this->roleId,
            'name' => $this->name,
            'email' => $this->email,
            'password' => $this->password,
            'must_change_password' => $this->mustChangePassword,
        ];
    }

    /**
     * The row as the users table wants it.
     *
     * @return array<string, mixed>
     */
    public function toRow(): array
    {
        return [
            'id' => $this->id,
            'role_id' => $this->roleId,
            'name' => $this->name,
            'email' => $this->email,
            'password' => $this->password,
            'must_change_password' => $this->mustChangePassword,
            'locale' => $this->locale,
            'picture' => $this->picture,
            'is_employee' => $this->isEmployee,
            'email_verified_at' => $this->emailVerifiedAt?->format('Y-m-d H:i:s'),
            'created_at' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt?->format('Y-m-d H:i:s'),
            'deleted_at' => $this->deletedAt?->format('Y-m-d H:i:s'),
        ];
    }
}
