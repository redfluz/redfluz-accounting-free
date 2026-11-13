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

class PurchaseOrderSettingData extends Data
{
    public function __construct(
        #[MapName('default_credit_term_days')]
        public ?int $defaultCreditTermDays = null,

        #[MapName('default_required_days')]
        public ?int $defaultRequiredDays = null,

        #[MapName('default_rfq_response_days')]
        public ?int $defaultRfqResponseDays = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toRow(): array
    {
        return [
            'default_credit_term_days' => $this->defaultCreditTermDays,
            'default_required_days' => $this->defaultRequiredDays,
            'default_rfq_response_days' => $this->defaultRfqResponseDays,
        ];
    }
}
