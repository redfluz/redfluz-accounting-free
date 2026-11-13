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

namespace App\Support\Data\System;

use App\Models\System\Setting;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;

/**
 * System settings: the singleton company profile row.
 *
 * This object holds the data and nothing else. It does not know it came from a
 * request, it does not look anything up, and it does not decide anything. A
 * controller builds one from whatever it is holding and then works from the
 * object.
 *
 * It is built two ways:
 *
 *   SystemSettingData::from($request->validated())   a form, to update
 *   SystemSettingData::fromModel($setting)            a saved one, to read it
 */
class SystemSettingData extends Data
{
    public function __construct(
        #[MapName('id')]
        public ?string $id = null,

        #[MapName('currency_id')]
        public ?string $currencyId = null,

        #[MapName('company_name')]
        public ?string $companyName = null,

        #[MapName('company_email')]
        public ?string $companyEmail = null,

        #[MapName('company_phone')]
        public ?string $companyPhone = null,

        #[MapName('company_mobile')]
        public ?string $companyMobile = null,

        #[MapName('company_fax')]
        public ?string $companyFax = null,

        #[MapName('company_website')]
        public ?string $companyWebsite = null,

        #[MapName('company_registration_no')]
        public ?string $companyRegistrationNo = null,

        #[MapName('company_tax_no')]
        public ?string $companyTaxNo = null,

        #[MapName('address')]
        public ?string $address = null,

        #[MapName('company_postcode')]
        public ?string $companyPostcode = null,

        #[MapName('company_city')]
        public ?string $companyCity = null,

        #[MapName('company_state')]
        public ?string $companyState = null,

        #[MapName('company_country')]
        public ?string $companyCountry = null,

        #[MapName('logo')]
        public mixed $logo = null,

        #[MapName('logo_path')]
        public ?string $logoPath = null,

        #[MapName('short_date_format')]
        public ?string $shortDateFormat = null,

        #[MapName('long_date_format')]
        public ?string $longDateFormat = null,

        #[MapName('created_at')]
        public ?CarbonImmutable $createdAt = null,

        #[MapName('updated_at')]
        public ?CarbonImmutable $updatedAt = null,

        #[MapName('deleted_at')]
        public ?CarbonImmutable $deletedAt = null,
    ) {}

    /**
     * The same moment, as the type this object holds.
     *
     * @param  CarbonInterface|null  $value  The carbon instance to convert.
     * @return CarbonImmutable|null The converted immutable carbon instance.
     */
    private static function moment(?CarbonInterface $value): ?CarbonImmutable
    {
        return $value === null ? null : CarbonImmutable::instance($value);
    }

    /**
     * Build from the model, which has already cast its own columns.
     *
     * @param  Setting  $model  The setting model instance.
     * @return self The populated data object.
     */
    public static function fromModel(Setting $model): self
    {
        return new self(
            id: $model->id,
            currencyId: $model->currency_id,
            companyName: $model->company_name,
            companyEmail: $model->company_email,
            companyPhone: $model->company_phone,
            companyMobile: $model->company_mobile,
            companyFax: $model->company_fax,
            companyWebsite: $model->company_website,
            companyRegistrationNo: $model->company_registration_no,
            companyTaxNo: $model->company_tax_no,
            address: $model->address,
            companyPostcode: $model->company_postcode,
            companyCity: $model->company_city,
            companyState: $model->company_state,
            companyCountry: $model->company_country,
            logoPath: $model->logo_path,
            shortDateFormat: $model->short_date_format,
            longDateFormat: $model->long_date_format,
            createdAt: self::moment($model->created_at),
            updatedAt: self::moment($model->updated_at),
            deletedAt: self::moment($model->deleted_at),
        );
    }

    /**
     * The columns a system setting is created from, and no others.
     *
     * @return array<string, mixed>
     */
    public function toCreateRow(): array
    {
        return [
            'currency_id' => $this->currencyId,
            'company_name' => $this->companyName,
            'company_email' => $this->companyEmail,
            'company_phone' => $this->companyPhone,
            'company_mobile' => $this->companyMobile,
            'company_fax' => $this->companyFax,
            'company_website' => $this->companyWebsite,
            'company_registration_no' => $this->companyRegistrationNo,
            'company_tax_no' => $this->companyTaxNo,
            'address' => $this->address,
            'company_postcode' => $this->companyPostcode,
            'company_city' => $this->companyCity,
            'company_state' => $this->companyState,
            'company_country' => $this->companyCountry,
            'logo_path' => $this->logoPath,
            'short_date_format' => $this->shortDateFormat,
            'long_date_format' => $this->longDateFormat,
        ];
    }

    /**
     * The row as the settings table wants it.
     *
     * @return array<string, mixed>
     */
    public function toRow(): array
    {
        return [
            'id' => $this->id,
            'currency_id' => $this->currencyId,
            'company_name' => $this->companyName,
            'company_email' => $this->companyEmail,
            'company_phone' => $this->companyPhone,
            'company_mobile' => $this->companyMobile,
            'company_fax' => $this->companyFax,
            'company_website' => $this->companyWebsite,
            'company_registration_no' => $this->companyRegistrationNo,
            'company_tax_no' => $this->companyTaxNo,
            'address' => $this->address,
            'company_postcode' => $this->companyPostcode,
            'company_city' => $this->companyCity,
            'company_state' => $this->companyState,
            'company_country' => $this->companyCountry,
            'logo_path' => $this->logoPath,
            'short_date_format' => $this->shortDateFormat,
            'long_date_format' => $this->longDateFormat,
            'created_at' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt?->format('Y-m-d H:i:s'),
            'deleted_at' => $this->deletedAt?->format('Y-m-d H:i:s'),
        ];
    }
}
