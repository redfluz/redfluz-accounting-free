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

use App\Models\Bank\CashAccount;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;

class CashAccountData extends Data
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

        #[MapName('asset_location_id')]
        public ?string $assetLocationId = null,

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

    public static function fromModel(CashAccount $model): self
    {
        return new self(
            id: $model->id,
            chartId: $model->chart_id,
            name: $model->name,
            accountName: $model->account_name,
            assetLocationId: $model->asset_location_id,
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
            // The form only collects "name"; the required account_name column
            // mirrors it so the row always satisfies the schema.
            'account_name' => $this->accountName ?? $this->name,
            'asset_location_id' => $this->assetLocationId,
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
            'account_name' => $this->accountName ?? $this->name,
            'asset_location_id' => $this->assetLocationId,
            'created_at' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt?->format('Y-m-d H:i:s'),
            'deleted_at' => $this->deletedAt?->format('Y-m-d H:i:s'),
        ];
    }
}
