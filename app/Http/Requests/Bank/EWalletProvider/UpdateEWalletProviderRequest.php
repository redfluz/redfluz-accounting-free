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

namespace App\Http\Requests\Bank\EWalletProvider;

use App\Enums\FileAction;
use App\Enums\FileResources;
use App\Models\Bank\EWalletProvider;
use Illuminate\Validation\Rule;

/**
 * The fields an e-wallet provider is edited from.
 *
 * The rules are stated in full rather than taken from the create request,
 * because an edit ignores the row's own identifier in the uniqueness checks,
 * and a rule inherited and then changed is a rule nobody can read in one
 * place.
 */
class UpdateEWalletProviderRequest extends EWalletProviderRequest
{
    /**
     * Who may edit an e-wallet provider.
     *
     * The permission lives here rather than in the controller, so that the
     * request says everything about who may send it and what it must contain.
     * Laravel asks this before the controller runs, so a request that is not
     * permitted never reaches the action it was meant to guard.
     */
    public function authorize(): bool
    {
        return $this->allows(FileResources::E_WALLET_PROVIDERS, FileAction::UPDATE);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $provider = $this->route('provider');
        $id = $provider instanceof EWalletProvider ? $provider->id : null;

        return [
            'id' => ['required', 'uuid', Rule::in([$id])],
            'name' => ['required', 'string', 'max:255', Rule::unique('e_wallet_providers', 'name')->ignore($id)],
            'country' => ['nullable', 'string', 'max:100'],
            'identifier_label' => ['nullable', 'string', 'max:50'],
            'chart_id' => ['nullable', 'uuid', Rule::exists('charts', 'id')->whereNull('deleted_at')],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
