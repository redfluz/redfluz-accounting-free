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

namespace App\Http\Requests\Purchase\PurchaseOrderSetting;

use App\Enums\FileAction;
use App\Enums\FileResources;

class UpdatePurchaseOrderSettingRequest extends PurchaseOrderSettingRequest
{
    public function authorize(): bool
    {
        return $this->allows(FileResources::PURCHASE_ORDER_SETTINGS, FileAction::UPDATE);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'default_credit_term_days' => ['required', 'integer', 'min:0', 'max:365'],
            'default_required_days' => ['required', 'integer', 'min:0', 'max:365'],
            'default_rfq_response_days' => ['required', 'integer', 'min:1', 'max:365'],
        ];
    }
}
