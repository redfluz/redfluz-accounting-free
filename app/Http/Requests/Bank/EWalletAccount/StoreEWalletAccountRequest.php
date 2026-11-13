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
            'name' => ['required', 'string', 'max:255'],
        ];
    }
}
