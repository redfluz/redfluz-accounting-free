<?php

/**
 * ============================================================================
 *
 * @author      redfluz
 *
 * @github      https://github.com/redfluz
 *
 * @youtube     https://youtube.com/@redfluz
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

use App\Models\Bank\EWalletAccount;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;

class EWalletAccountData extends Data
{
    public function __construct(
        #[MapName('id')]
        public ?string $id = null,

        #[MapName('chart_id')]
        public ?string $chartId = null,

        #[MapName('name')]
        public ?string $name = null,

        #[MapName('account_name')]
        public ?string $accountName = null,

        #[MapName('account_number')]
        public ?string $accountNumber = null,

        #[MapName('account_type')]
        public ?string $accountType = null,

        #[MapName('e_wallet_provider_id')]
        public ?string $eWalletProviderId = null,

        #[MapName('country_code')]
        public ?string $countryCode = null,

        #[MapName('email')]
        public ?string $email = null,

        #[MapName('phone')]
        public ?string $phone = null,

        #[MapName('website')]
        public ?string $website = null,

        #[MapName('address')]
        public ?string $address = null,

        #[MapName('notes')]
        public ?string $notes = null,

        #[MapName('is_active')]
        public ?bool $isActive = null,

        #[MapName('is_default')]
        public ?bool $isDefault = null,

        #[MapName('created_at')]
        public ?CarbonImmutable $createdAt = null,

        #[MapName('updated_at')]
        public ?CarbonImmutable $updatedAt = null,

        #[MapName('deleted_at')]
        public ?CarbonImmutable $deletedAt = null,
    ) {}

    private static function moment(?CarbonInterface $value): ?CarbonImmutable
    {
        return $value === null ? null : CarbonImmutable::instance($value);
    }

    public static function fromModel(EWalletAccount $model): self
    {
        return new self(
            id: $model->id,
            chartId: $model->chart_id,
            name: $model->name,
            accountName: $model->account_name,
            accountNumber: $model->account_number,
            accountType: $model->account_type,
            eWalletProviderId: $model->e_wallet_provider_id,
            countryCode: $model->country_code,
            email: $model->email,
            phone: $model->phone,
            website: $model->website,
            address: $model->address,
            notes: $model->notes,
            isActive: $model->is_active,
            isDefault: $model->is_default,
            createdAt: self::moment($model->created_at),
            updatedAt: self::moment($model->updated_at),
            deletedAt: self::moment($model->deleted_at),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toCreateRow(): array
    {
        return [
            'chart_id' => $this->chartId,
            'name' => $this->name,
            'account_name' => $this->accountName,
            'account_number' => $this->accountNumber,
            'account_type' => $this->accountType,
            'e_wallet_provider_id' => $this->eWalletProviderId,
            'country_code' => $this->countryCode,
            'email' => $this->email,
            'phone' => $this->phone,
            'website' => $this->website,
            'address' => $this->address,
            'notes' => $this->notes,
            'is_active' => $this->isActive,
            'is_default' => $this->isDefault ?? false,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toRow(): array
    {
        return [
            'id' => $this->id,
            'chart_id' => $this->chartId,
            'name' => $this->name,
            'account_name' => $this->accountName,
            'account_number' => $this->accountNumber,
            'account_type' => $this->accountType,
            'e_wallet_provider_id' => $this->eWalletProviderId,
            'country_code' => $this->countryCode,
            'email' => $this->email,
            'phone' => $this->phone,
            'website' => $this->website,
            'address' => $this->address,
            'notes' => $this->notes,
            'is_active' => $this->isActive,
            'is_default' => $this->isDefault ?? false,
            'created_at' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt?->format('Y-m-d H:i:s'),
            'deleted_at' => $this->deletedAt?->format('Y-m-d H:i:s'),
        ];
    }
}
