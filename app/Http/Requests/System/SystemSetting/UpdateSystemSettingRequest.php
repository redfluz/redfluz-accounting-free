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

namespace App\Http\Requests\System\SystemSetting;

use App\Enums\FileAction;
use App\Enums\FileResources;
use App\Enums\UploadType;
use Illuminate\Validation\Rule;

/**
 * The fields a system setting is edited from.
 *
 * Settings are updated rather than created, so this is the only request. The
 * logo is accepted here because replacing the company logo is an edit act.
 */
class UpdateSystemSettingRequest extends SystemSettingRequest
{
    /**
     * Who may edit system settings.
     *
     * The permission lives here rather than in the controller, so that the request
     * says everything about who may send it and what it must contain. Laravel
     * asks this before the controller runs, so a request that is not permitted
     * never reaches the action it was meant to guard.
     */
    public function authorize(): bool
    {
        return $this->allows(FileResources::SETTINGS, FileAction::UPDATE);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'currency_id' => ['nullable', 'uuid', Rule::exists('currencies', 'id')->whereNull('deleted_at')],
            'company_name' => ['required', 'string', 'max:255'],
            'company_email' => ['nullable', 'email', 'max:255'],
            'company_phone' => ['nullable', 'string', 'max:50'],
            'company_mobile' => ['nullable', 'string', 'max:50'],
            'company_fax' => ['nullable', 'string', 'max:50'],
            'company_website' => ['nullable', 'url', 'max:255'],
            'company_registration_no' => ['nullable', 'string', 'max:255'],
            'company_tax_no' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
            'company_postcode' => ['nullable', 'string', 'max:20'],
            'company_city' => ['nullable', 'string', 'max:255'],
            'company_state' => ['nullable', 'string', 'max:255'],
            'company_country' => ['nullable', 'string', 'max:255'],
            'short_date_format' => ['nullable', 'string', 'max:30'],
            'long_date_format' => ['nullable', 'string', 'max:30'],
            'logo' => ['nullable', 'image', UploadType::IMAGES->rule(), 'max:2048'],
        ];
    }
}
