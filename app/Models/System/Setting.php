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

namespace App\Models\System;

use App\Models\Finance\Currency;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Models\Concerns\HasActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * Singleton company profile row.
 *
 * Holds only the company identity printed on documents, invoices and PDFs, plus
 * the logo shown in the sidebar, top bar and login screen. There is exactly one
 * row; the settings controller creates it on first visit.
 *
 * @mixin Builder<Setting>
 *
 * @property string $id
 * @property string|null $currency_id
 * @property string|null $company_name
 * @property string|null $company_email
 * @property string|null $company_phone
 * @property string|null $company_fax
 * @property string|null $company_website
 * @property string|null $company_registration_no
 * @property string|null $company_tax_no
 * @property string|null $logo_path
 * @property string|null $address
 * @property string|null $company_postcode
 * @property string|null $company_city
 * @property string|null $company_state
 * @property string|null $company_country
 * @property string|null $company_mobile
 * @property string $short_date_format
 * @property string $long_date_format
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read Currency|null $currency
 * @property-read Collection<int, Activity> $activities
 * @property-read Collection<int, Activity> $activitiesAsCauser
 * @property-read Collection<int, Activity> $activitiesAsSubject
 */
#[Table('settings', key: 'id')]
#[Fillable([
    'currency_id',
    'company_name',
    'company_email',
    'company_phone',
    'company_fax',
    'company_website',
    'company_registration_no',
    'company_tax_no',
    'logo_path',
    'address',
    'company_postcode',
    'company_city',
    'company_state',
    'company_country',
    'company_mobile',
    'short_date_format',
    'long_date_format',
])]
class Setting extends Model
{
    use HasActivity, HasUuids, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'currency_id' => 'string',
            'company_name' => 'string',
            'company_email' => 'string',
            'company_phone' => 'string',
            'company_fax' => 'string',
            'company_website' => 'string',
            'company_registration_no' => 'string',
            'company_tax_no' => 'string',
            'logo_path' => 'string',
            'address' => 'string',
            'company_postcode' => 'string',
            'company_city' => 'string',
            'company_state' => 'string',
            'company_country' => 'string',
            'company_mobile' => 'string',
            'short_date_format' => 'string',
            'long_date_format' => 'string',
        ];
    }

    /**
     * Define an inverse one-to-one or many relationship.
     * The default currency for the system.
     *
     * @return BelongsTo<Currency, $this>
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    /**
     * Get the options for logging activity using Spatie Activitylog.
     *
     * @return LogOptions Returns configured log options.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty();
    }

    /**
     * Get the formatted date pattern from settings with caching.
     *
     * @param  string  $type  The date format column ('short_date_format' or 'long_date_format').
     * @return string The cached date format string.
     */
    public static function getDefaultDate(string $type = 'short_date_format'): string
    {
        $default = $type === 'long_date_format' ? 'd M Y, h:i A' : 'd M Y';

        return Cache::remember('system_date_format_'.$type, now()->addHours(24), function () use ($type, $default): string {
            $setting = static::first();

            if ($setting === null) {
                return $default;
            }

            $value = $setting->getAttribute($type);

            return is_string($value) && $value !== '' ? $value : $default;
        });
    }

    /**
     * Get the system currency symbol with caching.
     *
     * @return string The currency symbol (e.g. 'RM', '$') or empty string if not configured.
     */
    public static function getCurrencySymbol(): string
    {
        try {
            return Cache::remember('system_currency_symbol', now()->addHours(24), function (): string {
                $setting = static::first();

                if ($setting === null || $setting->currency_id === null) {
                    return '';
                }

                $symbol = $setting->currency?->symbol;

                return is_string($symbol) && $symbol !== '' ? $symbol : '';
            });
        } catch (\Throwable) {
            return '';
        }
    }
}
