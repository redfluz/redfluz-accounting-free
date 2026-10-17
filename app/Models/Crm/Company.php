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

namespace App\Models\Crm;

use App\Enums\LedgerLineType;
use App\Enums\LedgerSubType;
use App\Enums\LedgerType;
use App\Models\Finance\Ledger;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\JoinClause;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Models\Concerns\HasActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @mixin Builder<Company>
 *
 * @property string $id
 * @property string $name
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $address
 * @property string|null $logo_path
 * @property bool $is_debtor
 * @property bool $is_creditor
 * @property bool $is_courier
 * @property CarbonInterface $created_at
 * @property CarbonInterface $updated_at
 * @property-read Collection<int, Contact> $contacts
 * @property-read Collection<int, CompanyAddress> $addresses
 * @property-read Collection<int, ArLedger> $arLedgers
 * @property-read Collection<int, ApLedger> $apLedgers
 * @property-read Collection<int, Ledger> $ledgers
 * @property-read Collection<int, CompanyFile> $files
 *
 * @noinspection PhpUnused
 *
 * @property-read Collection<int, Activity> $activities
 * @property-read Collection<int, Activity> $activitiesAsCauser
 * @property-read Collection<int, Activity> $activitiesAsSubject
 */
#[Table('companies', key: 'id')]
#[Fillable([
    'name',
    'email',
    'phone',
    'address',
    'logo_path',
    'is_debtor',
    'is_creditor',
    'is_courier',
])]
class Company extends Model
{
    use HasActivity,HasUuids, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'name' => 'string',
            'email' => 'string',
            'phone' => 'string',
            'address' => 'string',
            'is_debtor' => 'boolean',
            'is_creditor' => 'boolean',
            'is_courier' => 'boolean',
        ];
    }

    /**
     * Define a one-to-many relationship.
     * Get the contacts for the company.
     *
     * @return HasMany<Contact, $this>
     */
    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }

    /**
     * Define a one-to-many relationship.
     * Get the saved branches and delivery addresses for the company.
     *
     * @return HasMany<CompanyAddress, $this>
     */
    public function addresses(): HasMany
    {
        return $this->hasMany(CompanyAddress::class)
            ->orderByDesc('is_default')
            ->orderBy('name');
    }

    /**
     * Define a one-to-many relationship.
     * Get the AR ledgers records for the company.
     *
     * @return HasMany<Ledger, $this>
     */
    public function ledgers(): HasMany
    {
        return $this->hasMany(Ledger::class);
    }

    /**
     * Define a one-to-many relationship.
     * The company's movements on the customer side of its account.
     *
     * @return HasMany<ArLedger, $this>
     */
    public function arLedgers(): HasMany
    {
        return $this->hasMany(ArLedger::class, 'company_id', 'id');
    }

    /**
     * Define a one-to-many relationship.
     * The company's movements on the supplier side of its account.
     *
     * @return HasMany<ApLedger, $this>
     */
    public function apLedgers(): HasMany
    {
        return $this->hasMany(ApLedger::class, 'company_id', 'id');
    }

    /**
     * Calculate total outstanding balance and earliest due date from posted sales invoices in the general ledger.
     *
     * @return array{outstanding_amount: float, due_date: string|null}
     */
    public function outstandingSummary(): array
    {
        $receiptTotals = Ledger::query()
            ->selectRaw('reference_number, SUM(total_amount) as received_amount')
            ->where('type', LedgerType::PAYMENT)
            ->where('subtype', LedgerSubType::RECEIPT)
            ->whereNotNull('credit_acc')
            ->whereNotNull('reference_number')
            ->groupBy('reference_number');

        $result = Ledger::query()
            ->from('ledgers as invoices')
            ->leftJoinSub($receiptTotals, 'receipt_totals', fn (JoinClause $join) => $join
                ->on('receipt_totals.reference_number', '=', 'invoices.document_number'))
            ->selectRaw('
                SUM(CASE WHEN invoices.line_total_amount > 0 THEN invoices.line_total_amount ELSE invoices.total_amount END)
                - COALESCE(MAX(receipt_totals.received_amount), 0) as outstanding_amount,
                MIN(invoices.due_date) as due_date
            ')
            ->where('invoices.company_id', $this->id)
            ->where('invoices.type', LedgerType::INVOICE->value)
            ->where('invoices.subtype', LedgerSubType::SALES_INVOICE->value)
            ->where('invoices.line_type', LedgerLineType::ITEM->value)
            ->whereNotNull('invoices.debit_acc')
            ->groupBy('invoices.company_id')
            ->first();

        $outstandingAmount = $result?->getAttribute('outstanding_amount');

        if (! $result || ! is_numeric($outstandingAmount) || (float) $outstandingAmount <= 0) {
            return ['outstanding_amount' => 0.00, 'due_date' => null];
        }

        return [
            'outstanding_amount' => (float) $outstandingAmount,
            'due_date' => $result->due_date,
        ];
    }

    /**
     * Define a one-to-many relationship.
     * Get all files and notes associated with the company.
     *
     * @return HasMany<CompanyFile, $this>
     */
    public function files(): HasMany
    {
        return $this->hasMany(CompanyFile::class, 'company_id');
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
}
