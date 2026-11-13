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
use App\Http\Requests\Crm\Contact\StoreContactRequest;
use App\Http\Requests\Crm\Contact\UpdateContactRequest;
use App\Models\Crm\Company;
use App\Models\Crm\Contact;
use App\Support\Data\Crm\ContactData;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ContactController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  Request  $request  The HTTP request instance.
     * @return View Returns the rendered Blade view.
     */
    public function index(Request $request): View
    {
        $this->allows(FileResources::CONTACTS);

        $search = $request->input('search');
        $character = $request->input('character');

        $query = Contact::query()->with('company');

        // Apply character filter (A-Z or '#' for non-alphabet)
        if (is_string($character) && $character !== '') {
            if ($character === '#') {
                // Match names that don't start with a letter
                $query->where('name', 'REGEXP', '^[^a-zA-Z]');
            } else {
                $query->where('name', 'like', $character.'%');
            }
        }

        // Apply search keyword filter
        if (is_string($search) && $search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                    ->orWhere('email', 'like', "%$search%")
                    ->orWhere('phone', 'like', "%$search%")
                    ->orWhere('position', 'like', "%$search%")
                    ->orWhereHas('company', fn ($cq) => $cq->where('name', 'like', "%$search%"));
            });
        }

        $contacts = $query
            ->latest()
            ->paginate(self::PER_PAGE_GRID_NORMAL)
            ->withQueryString();

        $alphabet = array_merge(['#'], range('A', 'Z'));

        return view('crm.contacts.index', compact('contacts', 'search', 'character', 'alphabet'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @param  Request  $request  The HTTP request instance containing an optional company ID.
     * @return View Returns the rendered Blade view.
     */
    public function create(Request $request): View
    {
        $this->allows(FileResources::CONTACTS, FileAction::ADD);
        $requestedCompanyId = $request->string('company')->toString();
        $selectedCompanyId = $requestedCompanyId !== ''
            ? Company::query()->whereKey($requestedCompanyId)->value('id')
            : null;
        /** @var Collection<int, Company> $companies */
        $companies = Company::query()
            ->orderBy('name')
            ->get();

        return view('crm.contacts.create', compact('companies', 'selectedCompanyId'));
    }

    /**
     * Handle the incoming request to create a new resource.
     * Validates the incoming request data and stores a new record in the database.
     *
     * @param  StoreContactRequest  $request  The validated contact being created.
     * @return RedirectResponse Redirects to the specified route.
     */
    public function store(StoreContactRequest $request): RedirectResponse
    {
        $data = ContactData::from($request->validated());

        Contact::query()->create($data->toCreateRow());

        return redirect()
            ->route('crm.contacts.index')
            ->with('success', FlashMessage::created(FileResources::CONTACTS));
    }

    /**
     * Show the form for editing an open resource.
     *
     * @param  Contact  $contact  The contact resource instance.
     * @return View Returns the rendered Blade view.
     */
    public function edit(Contact $contact): View
    {
        $this->allows(FileResources::CONTACTS, FileAction::UPDATE);
        /** @var Collection<int, Company> $companies */
        $companies = Company::query()
            ->orderBy('name')
            ->get();

        return view('crm.contacts.edit', compact('contact', 'companies'));
    }

    /**
     * Update an existing resource by its object.
     *
     * @param  UpdateContactRequest  $request  The validated contact being saved.
     * @param  Contact  $contact  The contact resource instance.
     * @return RedirectResponse Redirects to the specified route.
     */
    public function update(UpdateContactRequest $request, Contact $contact): RedirectResponse
    {
        $data = ContactData::from($request->validated());

        $contact->update($data->toRow());

        return redirect()
            ->route('crm.contacts.index')
            ->with('success', FlashMessage::updated(FileResources::CONTACTS));
    }

    /**
     * Remove a resource by its object.
     *
     * Refuses to delete a contact that is already named on a document or a
     * ledger entry, so the reference cannot outlive the contact in every picker.
     *
     * @param  Contact  $contact  The contact resource instance.
     * @return RedirectResponse Redirects to the specified route.
     */
    public function destroy(Contact $contact): RedirectResponse
    {
        $this->allows(FileResources::CONTACTS, FileAction::REMOVE);

        $blocked = $this->contactUsage($contact);

        if ($blocked !== []) {
            throw ValidationException::withMessages([
                'contact' => __('contacts.validation.in_use', [
                    'references' => implode(', ', $blocked),
                ]),
            ]);
        }

        $contact->delete();

        return redirect()
            ->route('crm.contacts.index')
            ->with('success', FlashMessage::deleted(FileResources::CONTACTS));
    }

    /**
     * List the documents and movements that still reference a contact.
     *
     * Queried through the query builder so soft-deleted rows still count, which
     * keeps a trashed document from freeing a contact and leaving a dangling
     * reference behind.
     *
     * @param  Contact  $contact  The contact resource instance.
     * @return list<string> Human readable names of the blocking references.
     */
    private function contactUsage(Contact $contact): array
    {
        $id = $contact->id;

        $checks = [
            __('contacts.usage.ledger_entries') => DB::table('ledgers')->where('contact_id', $id),
            // The sub-ledgers are the customer and supplier record, so they are
            // what says this person still appears on an open account.
            __('contacts.usage.customer_ledger_entries') => DB::table('ar_ledgers')->where('contact_id', $id),
            __('contacts.usage.supplier_ledger_entries') => DB::table('ap_ledgers')->where('contact_id', $id),
            __('contacts.usage.purchase_orders') => DB::table('purchase_orders')->where('contact_id', $id),
            __('contacts.usage.sales_orders') => DB::table('sales_orders')->where('contact_id', $id),
            __('contacts.usage.quotation_lines') => DB::table('quotations')->where('contact_id', $id),
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
