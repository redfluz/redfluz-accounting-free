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

namespace App\Http\Requests\Finance\Tax;

use App\Enums\FileAction;
use App\Enums\FileResources;
use App\Enums\TaxMode;
use App\Models\Finance\Tax;
use Illuminate\Validation\Rule;

/**
 * The fields a tax is edited from.
 *
 * The rules are stated in full rather than taken from the create request and
 * adjusted, because the one rule that differs is exactly the rule a reader would
 * want to see. The code must be unique except for the tax being edited, and a
 * rule inherited and then deleted is a rule that no longer exists anywhere to be
 * read.
 */
class UpdateTaxRequest extends TaxRequest
{
    /**
     * Who may edit a tax.
     *
     * The permission lives here rather than in the controller, so that the request
     * says everything about who may send it and what it must contain. Laravel
     * asks this before the controller runs, so a request that is not permitted
     * never reaches the action it was meant to guard.
     *
     * @return bool Whether the authenticated user may perform this action.
     */
    public function authorize(): bool
    {
        return $this->allows(FileResources::TAXES, FileAction::UPDATE);
    }

    /**
     * The validation rules for editing a tax.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Tax $tax */
        $tax = $this->route('tax');

        return [
            'id' => ['required', 'uuid', Rule::in([$tax->id])],
            'code' => ['required', 'string', 'max:50', Rule::unique('taxes', 'code')->ignore($tax->id)],
            'name' => ['required', 'string', 'max:255'],
            'rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'default_mode' => ['required', Rule::enum(TaxMode::class)],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
