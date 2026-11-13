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

use App\Http\Requests\BaseFormRequest;
use App\Support\Traits\ChecksFilePermission;

/**
 * The fields of a system setting, named.
 *
 * Settings are updated rather than created, so only an update request exists.
 * The naming of the fields is here so a controller asking for the company name
 * gets the same field whichever request it was handed.
 */
abstract class SystemSettingRequest extends BaseFormRequest
{
    use ChecksFilePermission;

    /**
     * The company name.
     */
    public function companyName(): string
    {
        return $this->stringOrEmpty('company_name');
    }

    /**
     * The company email, if supplied.
     */
    public function companyEmail(): ?string
    {
        return $this->stringOrNull('company_email');
    }

    /**
     * The company phone, if supplied.
     */
    public function companyPhone(): ?string
    {
        return $this->stringOrNull('company_phone');
    }

    /**
     * The company mobile, if supplied.
     */
    public function companyMobile(): ?string
    {
        return $this->stringOrNull('company_mobile');
    }

    /**
     * The company fax, if supplied.
     */
    public function companyFax(): ?string
    {
        return $this->stringOrNull('company_fax');
    }

    /**
     * The company website, if supplied.
     */
    public function companyWebsite(): ?string
    {
        return $this->stringOrNull('company_website');
    }

    /**
     * The company registration number, if supplied.
     */
    public function companyRegistrationNo(): ?string
    {
        return $this->stringOrNull('company_registration_no');
    }

    /**
     * The company tax number, if supplied.
     */
    public function companyTaxNo(): ?string
    {
        return $this->stringOrNull('company_tax_no');
    }

    /**
     * The address, if supplied.
     */
    public function address(): ?string
    {
        return $this->stringOrNull('address');
    }

    /**
     * The company postcode, if supplied.
     */
    public function companyPostcode(): ?string
    {
        return $this->stringOrNull('company_postcode');
    }

    /**
     * The company city, if supplied.
     */
    public function companyCity(): ?string
    {
        return $this->stringOrNull('company_city');
    }

    /**
     * The company state, if supplied.
     */
    public function companyState(): ?string
    {
        return $this->stringOrNull('company_state');
    }

    /**
     * The company country, if supplied.
     */
    public function companyCountry(): ?string
    {
        return $this->stringOrNull('company_country');
    }

    /**
     * The currency UUID, if supplied.
     */
    public function currencyId(): ?string
    {
        return $this->stringOrNull('currency_id');
    }

    /**
     * The short date format string, e.g. d/m/Y.
     */
    public function shortDateFormat(): ?string
    {
        return $this->stringOrNull('short_date_format');
    }

    /**
     * The long date format string, e.g. d M Y.
     */
    public function longDateFormat(): ?string
    {
        return $this->stringOrNull('long_date_format');
    }
}
