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

namespace App\Models\Bank;

use App\Models\ChartOfAccount\Chart;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A company that issues e-wallets: Touch 'n Go, GrabPay, Boost, and the rest.
 *
 * A provider is not an account. It is the issuer, and one provider issues many
 * accounts — a business may hold three Boost wallets as easily as one. So the
 * money sits on the account row in {@see Bank} and this table only says who
 * issued it, which is what makes a statement from that provider identifiable
 * when the ledger is read later.
 *
 * The table existed before this model did, and the accounts table carried a
 * foreign key to it the whole time, so nothing that wrote an e-wallet account
 * could name the issuer it belonged to. That is the gap this closes.
 *
 * @property string $id
 * @property string $name
 * @property string|null $country
 * @property string|null $identifier_label
 * @property string|null $chart_id
 * @property bool $is_active
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property CarbonInterface|null $deleted_at
 * @property-read Collection<int, Bank> $accounts
 * @property-read Chart|null $chart
 *
 * @noinspection PhpUnused
 */
#[Table('e_wallet_providers', key: 'id')]
#[Fillable([
    'name', 'country', 'identifier_label', 'chart_id', 'is_active',
])]
class EWalletProvider extends Model
{
    use HasUuids, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'chart_id' => 'string',
            'is_active' => 'boolean',
        ];
    }

    /**
     * The e-wallet accounts this provider issued.
     *
     * @return HasMany<Bank, $this>
     */
    public function accounts(): HasMany
    {
        return $this->hasMany(Bank::class, 'e_wallet_provider_id');
    }

    /**
     * The chart of account this provider's wallets are reported under.
     *
     * The binding may be empty because providers were seeded before any chart
     * was assigned to them, and removing a chart sets the reference to null
     * rather than removing the provider.
     *
     * @return BelongsTo<Chart, $this>
     */
    public function chart(): BelongsTo
    {
        return $this->belongsTo(Chart::class, 'chart_id');
    }

    /**
     * Limit the query to providers that may still be chosen.
     *
     * A retired provider stays on the table because its accounts are still on
     * the ledger and still have to be readable. It is only hidden from the
     * choices a new account can be made from.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Whether this provider may still be chosen for a new account.
     */
    public function isUsable(): bool
    {
        return $this->is_active;
    }
}
