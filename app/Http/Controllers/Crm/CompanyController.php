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

namespace App\Http\Controllers\Crm;

use App\Constants\FlashMessage;
use App\Enums\FileAction;
use App\Enums\FileResources;
use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\Company\StoreCompanyRequest;
use App\Http\Requests\Crm\Company\UpdateCompanyRequest;
use App\Models\Crm\ApLedger;
use App\Models\Crm\ArLedger;
use App\Models\Crm\Company;
use App\Models\Crm\CompanyFile;
use App\Support\Data\Crm\CompanyData;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class CompanyController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  Request  $request  The HTTP request instance.
     * @return View Returns the rendered Blade view.
     */
    public function index(Request $request): View
    {
        $this->allows(FileResources::COMPANIES);

        $search = $request->input('search');

        $query = Company::query();

        if (is_string($search) && $search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                    ->orWhere('email', 'like', "%$search%")
                    ->orWhere('phone', 'like', "%$search%");
            });
        }

        $companies = $query
            ->latest()
            ->paginate(self::PER_PAGE_GRID_NORMAL)
            ->withQueryString();

        return view('crm.companies.index', compact('companies', 'search'));
    }

    /**
     * Display the specified resource.
     * Display the company profile with separate AR and AP activity.
     *
     * @param  Request  $request  The HTTP request instance.
     * @param  Company  $company  The company resource instance.
     * @return View Returns the rendered company profile view.
     */
    public function show(Request $request, Company $company): View
    {
        $this->allows(FileResources::COMPANIES);

        $company->load([
            'contacts',
            'addresses',
            // No type hint: Laravel 13 passes the HasMany relation itself to
            // load() constraints, not a Builder, so a Builder hint throws.
            'files' => function ($q) {
                /** @var HasMany<CompanyFile, Company> $q */
                return $q->latest();
            },
        ]);

        $arLedgers = $this->accountStatement($company, 'isDebtor');
        $apLedgers = $this->accountStatement($company, 'isCreditor');
        $arTotals = $this->calculateStatement($arLedgers);
        $apTotals = $this->calculateStatement($apLedgers);

        // --- CALENDAR LOGIC ---
        // 1. Determine the month
        $month = $request->input('month');
        $calendarDate = is_string($month) && $month !== ''
            ? Carbon::parse($month)->startOfMonth()
            : Carbon::now()->startOfMonth();

        $daysInMonth = $calendarDate->daysInMonth;
        $startDayOfWeek = $calendarDate->dayOfWeek; // 0 (Sunday) to 6 (Saturday)

        // 2. Gather Events
        $events = collect();

        $statementLedgers = $arLedgers
            ->concat($apLedgers)
            ->unique('id');

        /** @var ArLedger|ApLedger $ledger */
        foreach ($statementLedgers as $ledger) {
            // A sub-ledger row is one movement, so the figure is whichever side
            // it carries: exactly one of the two is ever non-zero.
            $amount = (string) $ledger->debit_amount !== '0.00'
                ? (string) $ledger->debit_amount
                : (string) $ledger->credit_amount;

            $events->push([
                'date' => Carbon::parse($ledger->date)->format('Y-m-d'),
                'title' => $ledger->document_number.' ('.number_format((float) $amount, 2).')',
                'type' => 'transaction',
                'color' => 'info', // Light blue badge
                'icon' => 'receipt',
            ]);
        }

        // 3. Group by Y-m-d to plot on the grid
        $groupedEvents = $events->groupBy('date');

        return view('crm.companies.show', compact(
            'company',
            'calendarDate',
            'daysInMonth',
            'startDayOfWeek',
            'groupedEvents',
            'arLedgers',
            'apLedgers',
            'arTotals',
            'apTotals'
        ));
    }

    /**
     * Retrieve one company sub-ledger.
     *
     * The statement is read from ar_ledgers or ap_ledgers, not from the general
     * ledger. Those two tables already hold exactly the movements that belong to
     * this company — what it was invoiced, what it paid, what was refunded — and
     * every row names the document that caused it. Reading the general ledger
     * instead means re-deriving that subset on every page load, by matching chart
     * codes against the debtor and creditor charts, and it silently picks up
     * anything that happens to touch the same account: a journal entry debiting
     * the receivable is a company movement, but a transfer between two of the
     * business's own bank accounts is not.
     *
     * @param  Company  $company  The company resource instance.
     * @param  string  $accountMarker  Which sub-ledger to read: isDebtor or
     *                                 isCreditor.
     * @return Collection<int, ArLedger>|Collection<int, ApLedger> The ordered company sub-ledger rows.
     */
    private function accountStatement(Company $company, string $accountMarker): Collection
    {
        if ($accountMarker === 'isDebtor') {
            return ArLedger::query()
                ->where('company_id', $company->id)
                ->whereNull('deleted_at')
                ->oldest('date')
                ->oldest('created_at')
                ->get();
        }

        return ApLedger::query()
            ->where('company_id', $company->id)
            ->whereNull('deleted_at')
            ->oldest('date')
            ->oldest('created_at')
            ->get();
    }

    /**
     * Add running balances and return statement totals.
     *
     * @param  Collection<int, ArLedger>|Collection<int, ApLedger>  $ledgers  The statement rows.
     * @return array{debit: string, credit: string, balance: string} Statement totals.
     */
    private function calculateStatement(Collection $ledgers): array
    {
        $debitTotal = '0.00';
        $creditTotal = '0.00';
        $balance = '0.00';

        foreach ($ledgers as $ledger) {
            $debitAmount = (string) $ledger->debit_amount;
            $creditAmount = (string) $ledger->credit_amount;
            // Signed from our perspective: positive is money in our favour.
            // A customer balance is debit-normal, so owing reads positive; a
            // supplier balance is credit-normal, so owing reads negative and
            // only an advance to the supplier reads positive.
            $movement = bcsub($debitAmount, $creditAmount, 2);

            $debitTotal = bcadd($debitTotal, $debitAmount, 2);
            $creditTotal = bcadd($creditTotal, $creditAmount, 2);
            $balance = bcadd($balance, $movement, 2);

            $ledger->setAttribute('running_balance', $balance);
        }

        return [
            'debit' => $debitTotal,
            'credit' => $creditTotal,
            'balance' => $balance,
        ];
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return View Returns the rendered Blade view.
     */
    public function create(): View
    {
        $this->allows(FileResources::COMPANIES, FileAction::ADD);

        return view('crm.companies.create');
    }

    /**
     * Handle the incoming request to create a new resource.
     * Validates the incoming request data and stores a new record in the database.
     *
     * @param  StoreCompanyRequest  $request  The validated company being created.
     * @return RedirectResponse Redirects to the specified route.
     */
    public function store(StoreCompanyRequest $request): RedirectResponse
    {
        $data = CompanyData::from($request->validated());

        if ($request->hasFile('logo')) {
            $data->logoPath = $this->storeUpload($request->file('logo'), 'companies', self::PUBLIC_DISK);
        }

        $company = Company::query()->create($data->toCreateRow());
        if (filled($company->address)) {
            $company->addresses()->create([
                'name' => __('companies.addresses.main_address'),
                'phone' => $company->phone,
                'address' => $company->address,
                'is_default' => true,
            ]);
        }

        return redirect()
            ->route('crm.companies.index')
            ->with('success', FlashMessage::created(FileResources::COMPANIES));
    }

    /**
     * Show the form for editing an open resource.
     *
     * @param  Company  $company  The company resource instance.
     * @return View Returns the rendered Blade view.
     */
    public function edit(Company $company): View
    {
        $this->allows(FileResources::COMPANIES, FileAction::UPDATE);

        return view('crm.companies.edit', compact('company'));
    }

    /**
     * Update an existing resource by its object.
     *
     * @param  UpdateCompanyRequest  $request  The validated company being saved.
     * @param  Company  $company  The company resource instance.
     * @return RedirectResponse Redirects to the specified route.
     */
    public function update(UpdateCompanyRequest $request, Company $company): RedirectResponse
    {
        $data = CompanyData::from($request->validated());

        if ($request->hasFile('logo')) {
            $newLogoPath = $this->storeUpload($request->file('logo'), 'companies', self::PUBLIC_DISK);
            $oldLogoPath = $company->logo_path;
            $data->logoPath = $newLogoPath;
            $company->update($data->toRow());

            if ($oldLogoPath && Storage::disk('public')->exists($oldLogoPath)) {
                Storage::disk('public')->delete($oldLogoPath);
            }
        } else {
            $company->update($data->toRow());
        }
        if (filled($company->address) && ! $company->addresses()->exists()) {
            $company->addresses()->create([
                'name' => __('companies.addresses.main_address'),
                'phone' => $company->phone,
                'address' => $company->address,
                'is_default' => true,
            ]);
        }

        return redirect()
            ->route('crm.companies.index')
            ->with('success', FlashMessage::updated(FileResources::COMPANIES));
    }

    /**
     * Remove a resource by its object.
     *
     * Refuses to delete a company that is already referenced. A company can be a
     * debtor, a creditor and a courier at the same time, and each of those links
     * keeps pointing at the company after a soft delete, so the party silently
     * disappears from every picker while its documents still name it.
     *
     * Addresses, files and contacts belong to the company and are not counted as
     * usage.
     *
     * @param  Company  $company  The company resource instance.
     * @return RedirectResponse Redirects to the specified route.
     *
     * @throws Throwable
     */
    public function destroy(Company $company): RedirectResponse
    {
        $this->allows(FileResources::COMPANIES, FileAction::REMOVE);

        $blocked = $this->companyUsage($company);

        if ($blocked !== []) {
            throw ValidationException::withMessages([
                'company' => __('companies.validation.in_use', [
                    'references' => implode(', ', $blocked),
                ]),
            ]);
        }

        if ($company->logo_path && Storage::disk('public')->exists($company->logo_path)) {
            Storage::disk('public')->delete($company->logo_path);
        }

        // Addresses, files, and contacts are not counted as usage, so a company
        // holding only those can be deleted. Soft delete the children in the
        // same transaction: leaving them live orphans them, and the address
        // listing resolves its company link through the relation, so a row
        // pointing at a trashed company raises a UrlGenerationException and
        // takes the whole page down with it.
        DB::transaction(function () use ($company): void {
            // Query builder rather than the relations: Company::addresses()
            // carries an ORDER BY that has no meaning for an update.
            $now = now();

            foreach (['company_addresses', 'contacts', 'company_files'] as $table) {
                DB::table($table)
                    ->where('company_id', $company->id)
                    ->whereNull('deleted_at')
                    ->update(['deleted_at' => $now]);
            }

            $company->delete();
        });

        return redirect()
            ->route('crm.companies.index')
            ->with('success', FlashMessage::deleted(FileResources::COMPANIES));
    }

    /**
     * List the documents and movements that still reference a company.
     *
     * Queried through the query builder so soft-deleted rows still count. A
     * trashed document must not free a company for deletion and leave the
     * reference dangling.
     *
     * @param  Company  $company  The company resource instance.
     * @return list<string> Human readable names of the blocking references.
     */
    private function companyUsage(Company $company): array
    {
        $id = $company->id;

        $checks = [
            __('companies.usage.ledger_entries') => DB::table('ledgers')->where('company_id', $id),
            // The sub-ledgers are the customer and supplier record, so they are
            // what says a company still owes or is still owed. The general
            // ledger holds every movement including ones naming no company, so
            // on its own it cannot distinguish a company still in use.
            __('companies.usage.customer_ledger_entries') => DB::table('ar_ledgers')->where('company_id', $id),
            __('companies.usage.supplier_ledger_entries') => DB::table('ap_ledgers')->where('company_id', $id),
            __('companies.usage.product_movements') => DB::table('product_ledgers')->where('company_id', $id),
            __('companies.usage.purchase_orders') => DB::table('purchase_orders')->where('company_id', $id),
            __('companies.usage.sales_orders') => DB::table('sales_orders')->where('company_id', $id),
            __('companies.usage.quotation_lines') => DB::table('quotations')->where('company_id', $id),
            __('companies.usage.delivery_orders') => DB::table('delivery_orders')->where('company_id', $id),
            __('companies.usage.courier_deliveries') => DB::table('delivery_orders')->where('courier_company_id', $id),
            __('companies.usage.supplied_products') => DB::table('products')->where('supplier_company_id', $id),
        ];

        $blocked = [];
        foreach ($checks as $label => $query) {
            if ($query->exists()) {
                $blocked[] = $label;
            }
        }

        return $blocked;
    }
}
