<?php

namespace App\Http\Controllers\Crm;

use App\Enums\FileAction;
use App\Enums\FileResources;
use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\CompanyAddress\StoreCompanyAddressRequest;
use App\Http\Requests\Crm\CompanyAddress\UpdateCompanyAddressRequest;
use App\Models\Crm\Company;
use App\Models\Crm\CompanyAddress;
use App\Support\Data\Crm\CompanyAddressData;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class CompanyAddressController extends Controller
{
    /**
     * Display a listing of the resource.
     * Display a listing of company addresses with filtering options.
     *
     * @param  Request  $request  The HTTP request instance.
     * @return View Returns the rendered Blade view.
     */
    public function index(Request $request): View
    {
        $this->allows(FileResources::COMPANIES);
        $search = $request->string('search')->toString();
        $companyId = $request->string('company')->toString();
        $addresses = CompanyAddress::query()
            ->with('company')
            ->when($companyId !== '', fn ($query) => $query->where('company_id', $companyId))
            ->when($search !== '', fn ($query) => $query->where(function ($subQuery) use ($search): void {
                $subQuery->where('name', 'like', "%$search%")
                    ->orWhere('phone', 'like', "%$search%")
                    ->orWhere('address', 'like', "%$search%")
                    ->orWhereHas('company', fn ($companyQuery) => $companyQuery->where('name', 'like', "%$search%"));
            }))
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->paginate(self::PER_PAGE_GRID_NORMAL)
            ->withQueryString();

        /** @var Collection<int, Company> $companies */
        $companies = Company::query()->orderBy('name')->get();

        return view('crm.company-addresses.index', compact('addresses', 'companies', 'companyId', 'search'));
    }

    /**
     * Show the form for creating a new resource.
     * Show the form for creating a company address.
     *
     * @param  Request  $request  The HTTP request instance.
     * @param  Company|null  $company  The optional company resource instance.
     * @return View Returns the rendered Blade view.
     */
    public function create(Request $request, ?Company $company = null): View
    {
        $this->allows(FileResources::COMPANIES, FileAction::ADD);
        $requestedCompanyId = $request->string('company')->toString();
        $company ??= $requestedCompanyId !== '' ? Company::query()->find($requestedCompanyId) : null;
        /** @var Collection<int, Company> $companies */
        $companies = Company::query()->orderBy('name')->get();

        return view('crm.company-addresses.create', compact('companies', 'company'));
    }

    /**
     * Handle the incoming request to create a new resource.
     * Validate and store a company address.
     *
     * @param  StoreCompanyAddressRequest  $request  The validated company address being created.
     * @param  Company|null  $company  The optional company resource instance.
     * @return RedirectResponse Redirects to the company address listing.
     *
     * @throws Throwable
     */
    public function store(StoreCompanyAddressRequest $request, ?Company $company = null): RedirectResponse
    {
        if ($company === null) {
            $companyId = $request->validated('company_id');
            $company = Company::query()->findOrFail(is_string($companyId) ? $companyId : '');
        }

        $data = CompanyAddressData::from($request->validated());
        $data->companyId = $company->id;

        DB::transaction(function () use ($company, $data): void {
            $isDefault = ($data->isDefault ?? false) || ! $company->addresses()->exists();
            if ($isDefault) {
                $company->addresses()->update(['is_default' => false]);
            }

            $company->addresses()->create([
                ...$data->toCreateRow(),
                'is_default' => $isDefault,
            ]);
        });

        return redirect()->route('crm.company-addresses.index', ['company' => $company->id])
            ->with('success', __('companies.addresses.messages.created'));
    }

    /**
     * Show the form for editing a company address.
     *
     * @param  CompanyAddress  $companyAddress  The company address resource instance.
     * @return View Returns the rendered Blade view.
     */
    public function edit(CompanyAddress $companyAddress): View
    {
        $this->allows(FileResources::COMPANIES, FileAction::UPDATE);
        $company = $companyAddress->company;

        return view('crm.company-addresses.edit', compact('company', 'companyAddress'));
    }

    /**
     * Update an existing resource by its object.
     * Validate and update a company address.
     *
     * @param  UpdateCompanyAddressRequest  $request  The validated company address being saved.
     * @param  CompanyAddress  $companyAddress  The company address resource instance.
     * @return RedirectResponse Redirects to the company address listing.
     *
     * @throws Throwable
     */
    public function update(UpdateCompanyAddressRequest $request, CompanyAddress $companyAddress): RedirectResponse
    {
        $data = CompanyAddressData::from($request->validated());
        $company = $companyAddress->company()->firstOrFail();

        DB::transaction(function () use ($company, $companyAddress, $data): void {
            $isDefault = $data->isDefault ?? false;
            if ($isDefault) {
                $company->addresses()->whereKeyNot($companyAddress->id)->update(['is_default' => false]);
            }

            // The edit form does not post company_id — the row already belongs
            // to a company and this form cannot move it — so it is absent from
            // the validated data and arrives here as null. Passing that null
            // through toRow() writes it over a NOT NULL column and every
            // address edit died on an integrity violation.
            $row = $data->toRow();
            unset($row['company_id'], $row['id']);

            $companyAddress->update([...$row, 'is_default' => $isDefault]);

            if (! $company->addresses()->where('is_default', true)->exists()) {
                $companyAddress->update(['is_default' => true]);
            }
        });

        return redirect()->route('crm.company-addresses.index', ['company' => $company->id])
            ->with('success', __('companies.addresses.messages.updated'));
    }

    /**
     * Remove a resource by its object.
     * Remove a company address and handle default address reassignment if necessary.
     *
     * @param  CompanyAddress  $companyAddress  The company address resource instance.
     * @return RedirectResponse Redirects to the company address listing.
     *
     * @throws Throwable
     */
    public function destroy(CompanyAddress $companyAddress): RedirectResponse
    {
        $this->allows(FileResources::COMPANIES, FileAction::REMOVE);
        $company = $companyAddress->company()->firstOrFail();

        DB::transaction(function () use ($company, $companyAddress): void {
            $wasDefault = $companyAddress->is_default;
            $companyAddress->delete();

            if ($wasDefault) {
                $company->addresses()->first()?->update(['is_default' => true]);
            }
        });

        return redirect()->route('crm.company-addresses.index', ['company' => $company->id])
            ->with('success', __('companies.addresses.messages.deleted'));
    }
}
