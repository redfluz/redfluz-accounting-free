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

namespace App\Http\Requests\Bank\CashAccount;

use App\Enums\FileAction;
use App\Enums\FileResources;
use App\Models\Bank\CashAccount;
use Illuminate\Validation\Rule;

class UpdateCashAccountRequest extends CashAccountRequest
{
    public function authorize(): bool
    {
        return $this->allows(FileResources::BANKS, FileAction::UPDATE);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var CashAccount $cashAccount */
        $cashAccount = $this->route('cash_account');
        $cashAccountId = $cashAccount->id;

        return [
            'id' => ['required', 'uuid', Rule::in([$cashAccountId])],
            'chart_id' => ['required', 'uuid', Rule::exists('charts', 'id')->where('isBank', true)->whereNull('deleted_at'), Rule::unique('cash_accounts', 'chart_id')->ignore($cashAccountId)],
            'asset_location_id' => ['nullable', 'uuid', Rule::exists('stock_locations', 'id')->whereNull('deleted_at')],
            'float_amount' => ['nullable', 'numeric', 'min:0'],
            'is_default' => ['nullable', 'boolean'],
            'name' => ['required', 'string', 'max:255'],
            'account_name' => ['required', 'string', 'max:255'],
            'account_type' => ['nullable', 'string', 'max:100'],
            'country_code' => ['required', 'alpha', 'size:2'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:100'],
            'website' => ['nullable', 'url', 'max:255'],
            'address' => ['nullable', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
