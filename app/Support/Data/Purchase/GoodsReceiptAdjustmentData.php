<?php

/**
 * ============================================================================
 *
 * @author      redfluz
 *
 * @github      https://github.com/redfluz
 *
 * @youtube     https://github.com/redfluz
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

namespace App\Support\Data\Purchase;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;

class GoodsReceiptAdjustmentData extends Data
{
    /**
     * @param  array<int, array<string, mixed>>|null  $lines
     */
    public function __construct(
        #[MapName('date')]
        public ?string $date = null,

        #[MapName('notes')]
        public ?string $notes = null,

        #[MapName('lines')]
        public ?array $lines = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toCreateRow(): array
    {
        return [
            'date' => $this->date,
            'notes' => $this->notes,
            'lines' => $this->lines ?? [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toRow(): array
    {
        return [
            'date' => $this->date,
            'notes' => $this->notes,
            'lines' => $this->lines ?? [],
        ];
    }
}
