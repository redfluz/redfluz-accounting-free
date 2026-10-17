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

namespace App\Http\Requests\Bank\EWalletAccount;

use App\Enums\FileAction;
use App\Enums\FileResources;
use Illuminate\Validation\Rule;

class StoreEWalletAccountRequest extends EWalletAccountRequest
{
    public function authorize(): bool
    {
        return $this->allows(FileResources::BANKS, FileAction::ADD);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'chart_id' => ['required', 'uuid', Rule::exists('charts', 'id')->where('isBank', true)->whereNull('deleted_at'), Rule::unique('e_wallet_accounts', 'chart_id')],
            'e_wallet_provider_id' => ['required', 'uuid', Rule::exists('e_wallet_providers', 'id')->whereNull('deleted_at')],
            'is_default' => ['nullable', 'boolean'],
            'name' => ['required', 'string', 'max:255'],
            'account_name' => ['required', 'string', 'max:255'],
            'account_number' => ['nullable', 'string', 'max:100'],
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
