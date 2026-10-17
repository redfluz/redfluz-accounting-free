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

namespace App\Http\Controllers\Sales;

use App\Enums\FileAction;
use App\Enums\FileResources;
use App\Enums\LedgerStatus;
use App\Enums\LedgerSubType;
use App\Enums\QuotationStatus;
use App\Enums\SalesOrderStatus;
use App\Enums\SalesType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Sales\Quotation\StoreQuotationRequest;
use App\Http\Requests\Sales\Quotation\UpdateQuotationRequest;
use App\Models\Crm\Company;
use App\Models\Crm\Contact;
use App\Models\Inventory\Product;
use App\Models\Sales\Quotation;
use App\Models\Sales\QuotationLine;
use App\Models\Sales\SalesInvoice;
use App\Models\Sales\SalesInvoiceLine;
use App\Models\Sales\SalesOrder;
use App\Models\Sales\SalesOrderLine;
use App\Models\System\Setting;
use App\Services\DecimalService;
use App\Support\Data\Sales\QuotationData;
use App\Traits\GeneratesDocumentNumber;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class QuotationController extends Controller
{
    use GeneratesDocumentNumber;

    /**
     * Display a listing of the resource.
     *
     * @param  Request  $request  The HTTP request instance.
     * @return View Returns the rendered Blade view.
     */
    public function index(Request $request): View
    {
        $this->allows(FileResources::QUOTATIONS);

        $search = $request->string('search')->toString();
        $startDate = $request->string('start_date')->toString();
        $endDate = $request->string('end_date')->toString();
        $quotations = Quotation::query()
            ->with(['convertedSalesOrder', 'convertedInvoice'])
            ->withSum('lines as total_amount', 'total_amount')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($subQuery) use ($search): void {
                    $subQuery->where('document_number', 'like', "%$search%")
                        ->orWhere('reference_number', 'like', "%$search%")
                        ->orWhere('company_name', 'like', "%$search%");
                });
            })
            ->when($startDate !== '', fn ($query) => $query->whereDate('date', '>=', $startDate))
            ->when($endDate !== '', fn ($query) => $query->whereDate('date', '<=', $endDate))
            ->latest('date')
            ->paginate(self::PER_PAGE_GRID_NORMAL)
            ->withQueryString();

        return view('sales.quotations.index', compact('quotations', 'search', 'startDate', 'endDate'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return View Returns the rendered Blade view.
     */
    public function create(): View
    {
        $this->authorizeQuotation(FileAction::ADD);

        return view('sales.quotations.create', $this->formData());
    }

    /**
     * Create a customer without leaving the Quotation form.
     *
     * @param  Request  $request  The HTTP request instance.
     * @return JsonResponse Returns the new customer for the form selector.
     */
    public function storeCompany(Request $request): JsonResponse
    {
        $this->allows(FileResources::QUOTATIONS, FileAction::ADD);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);
        $companyName = is_array($validated) && is_string($validated['name'] ?? null) ? $validated['name'] : '';
        $company = Company::query()->create([
            'name' => trim($companyName),
            'is_debtor' => true,
        ]);

        return response()->json(['id' => $company->id, 'name' => $company->name], 201);
    }

    /**
     * Handle the incoming request to create a new resource.
     *
     * @param  StoreQuotationRequest  $request  The HTTP request instance.
     * @return RedirectResponse Redirects to the specified route.
     *
     * @throws Throwable
     */
    public function store(StoreQuotationRequest $request): RedirectResponse
    {
        $data = QuotationData::from($request->validated());

        $company = Company::query()->whereKey($data->companyId)->where('is_debtor', true)->firstOrFail();
        /** @var Collection<int, array<string, mixed>> $linesCollection */
        $linesCollection = $request->collect('lines');
        $lines = $linesCollection->filter(
            fn (array $line): bool => filled($line['product_id'] ?? null) || filled($line['description'] ?? null)
        )->values();
        if ($lines->isEmpty()) {
            throw ValidationException::withMessages(['lines' => __('quotations.messages.lines_required')]);
        }
        /** @var EloquentCollection<string, Product> $products */
        $products = Product::query()->whereIn('id', $lines->pluck('product_id')->filter()->unique())->get()->keyBy('id');
        $documentNumber = $this->generateQuotationNumber();
        $attachmentPath = $request->hasFile('attachment') ? $this->storeUpload($request->file('attachment'), 'quotations') : null;
        $now = now();

        DB::transaction(function () use ($attachmentPath, $company, $lines, $now, $products, $documentNumber, $data): void {
            $quotation = new Quotation;
            $quotation->fill([
                'company_id' => $company->id,
                'company_name' => $company->name,
                'contact_id' => $data->contactId,
                'document_number' => $documentNumber,
                'reference_number' => $data->referenceNumber,
                'date' => $data->date,
                'expiry_date' => $data->expiryDate,
                'attachment_path' => $attachmentPath,
                'status' => $data->status !== null ? $data->status->value : QuotationStatus::DRAFT->value,
                'notes' => $data->notes,
            ]);
            $quotation->created_at = $now;
            $quotation->updated_at = $now;
            $quotation->save();

            foreach ($lines as $index => $line) {
                $quotation->lines()->create($this->lineRow($line, (int) $index, $products));
            }
        });

        return redirect()->route('sales.quotations.show', $documentNumber);
    }

    /**
     * Display the specified resource.
     *
     * @param  string  $quotationNumber  The quotation number value.
     * @return View Returns the rendered Blade view.
     */
    public function show(string $quotationNumber): View
    {
        $quotation = $this->quotationByNumber($quotationNumber);
        $this->authorizeQuotation();

        return view(
            'sales.quotations.show',
            $this->formData() + [
                'quotation' => $quotation,
                'lines' => $quotation->lines,
                'quotationNumber' => $quotationNumber,
            ]
        );
    }

    /**
     * Show the form for editing an open resource.
     *
     * @param  string  $quotationNumber  The quotation number value.
     * @return View Returns the rendered Blade view.
     */
    public function edit(string $quotationNumber): View
    {
        $quotation = $this->quotationByNumber($quotationNumber);
        $this->authorizeQuotation(FileAction::UPDATE);
        abort_if($quotation->is_converted, 409);

        return view(
            'sales.quotations.edit',
            $this->formData() + [
                'quotation' => $quotation,
                'lines' => $quotation->lines,
                'quotationNumber' => $quotationNumber,
            ]
        );
    }

    /**
     * Update an existing resource by its object.
     *
     * @param  UpdateQuotationRequest  $request  The HTTP request instance.
     * @param  string  $quotationNumber  The quotation number value.
     * @return RedirectResponse Redirects to the specified route.
     *
     * @throws Throwable
     */
    public function update(UpdateQuotationRequest $request, string $quotationNumber): RedirectResponse
    {
        $quotation = $this->quotationByNumber($quotationNumber);
        abort_if($quotation->is_converted, 409);

        $data = QuotationData::from($request->validated());

        $company = Company::query()->whereKey($data->companyId)->where('is_debtor', true)->firstOrFail();
        /** @var Collection<int, array<string, mixed>> $linesCollection */
        $linesCollection = $request->collect('lines');
        $lines = $linesCollection->filter(
            fn (array $line): bool => filled($line['product_id'] ?? null) || filled($line['description'] ?? null)
        )->values();
        if ($lines->isEmpty()) {
            throw ValidationException::withMessages(['lines' => __('quotations.messages.lines_required')]);
        }
        /** @var EloquentCollection<string, Product> $products */
        $products = Product::query()->whereIn('id', $lines->pluck('product_id')->filter()->unique())->get()->keyBy('id');
        $attachmentPath = $request->hasFile('attachment')
            ? $this->storeUpload($request->file('attachment'), 'quotations')
            : $quotation->attachment_path;

        DB::transaction(function () use ($attachmentPath, $company, $lines, $products, $quotation, $data): void {
            $quotation->fill([
                'company_id' => $company->id,
                'company_name' => $company->name,
                'contact_id' => $data->contactId,
                'reference_number' => $data->referenceNumber,
                'date' => $data->date,
                'expiry_date' => $data->expiryDate,
                'attachment_path' => $attachmentPath,
                'status' => $data->status !== null ? $data->status->value : QuotationStatus::DRAFT->value,
                'notes' => $data->notes,
            ]);
            $quotation->save();

            // The lines are the offer, and the offer is replaced wholesale, so an
            // edit cannot leave an old amount standing behind a changed form.
            $quotation->lines()->delete();

            foreach ($lines as $index => $line) {
                $quotation->lines()->create($this->lineRow($line, (int) $index, $products));
            }
        });

        return redirect()->route('sales.quotations.show', $quotationNumber);
    }

    /**
     * Generate the PDF representation of the resource.
     *
     * @param  string  $quotationNumber  The quotation number value.
     * @return Response Returns the HTTP response.
     */
    public function pdf(string $quotationNumber): Response
    {
        $quotation = $this->quotationByNumber($quotationNumber);
        $this->authorizeQuotation();

        return Pdf::loadView('sales.quotations.pdf', [
            'lines' => $quotation->lines,
            'quotation' => $quotation,
            'setting' => Setting::query()->first(),
        ])->setPaper('a4')->stream($quotationNumber.'.pdf');
    }

    /**
     * Convert a physical-product quotation to a Sales Order, or a service quotation directly to an invoice.
     *
     *
     * @param  string  $quotationNumber  The quotation number value.
     * @return RedirectResponse Redirects to the specified route.
     *
     * @throws Throwable
     */
    public function convert(string $quotationNumber): RedirectResponse
    {
        $conversion = DB::transaction(function () use ($quotationNumber): array {
            /** @var Quotation|null $quotation */
            $quotation = Quotation::query()
                ->with('lines')
                ->where('document_number', $quotationNumber)
                ->lockForUpdate()
                ->first();

            abort_if($quotation === null, 404);
            $this->authorizeQuotation(FileAction::ADD);
            abort_if($quotation->is_converted, 409);
            abort_if($quotation->status !== QuotationStatus::ACCEPTED, 422, __('quotations.messages.accepted_required'));

            $now = now();
            /** @var EloquentCollection<int, QuotationLine> $quotedLines */
            $quotedLines = $quotation->lines;
            /** @var EloquentCollection<string, Product> $products */
            $products = Product::query()->whereIn('id', $quotedLines->pluck('product_id')->filter()->unique())->get()->keyBy('id');
            $hasPhysicalProduct = $quotedLines->contains(function (QuotationLine $line) use ($products): bool {
                $product = filled($line->product_id) ? $products->get($line->product_id) : null;

                return $product !== null && ! $product->is_service;
            });

            if ($hasPhysicalProduct) {
                $sumTotal = $quotedLines->sum('total_amount');
                $quotationTotal = number_format(is_numeric($sumTotal) ? (float) $sumTotal : 0.0, 2, '.', '');
                $salesOrder = new SalesOrder;
                $salesOrder->fill([
                    'company_id' => $quotation->company_id,
                    'contact_id' => $quotation->contact_id,
                    'quotation_id' => $quotation->id,
                    'document_number' => $this->generateSalesOrderNumber(),
                    'customer_reference_number' => $quotation->reference_number,
                    'company_name' => $quotation->company_name,
                    'contact_name' => $quotation->contact?->name,
                    'order_date' => $quotation->getRawOriginal('date'),
                    'expected_delivery_date' => null,
                    'status' => SalesOrderStatus::DRAFT,
                    'subtotal' => $quotationTotal,
                    'total_amount' => $quotationTotal,
                    'attachment_path' => $quotation->attachment_path,
                    'notes' => $quotation->notes,
                ])->save();
                $salesOrderRows = $quotedLines->map(function (QuotationLine $line) use ($now, $products, $salesOrder): array {
                    $product = $products->firstWhere('id', $line->product_id);

                    return [
                        'id' => (string) Str::uuid7(),
                        'sales_order_id' => $salesOrder->id,
                        'product_id' => $product?->id,
                        'product_sku' => $product?->sku,
                        'product_name' => $product instanceof Product ? $product->name : ($line->description ?? '-'),
                        'description' => $line->description,
                        'is_service' => $product instanceof Product ? $product->is_service : true,
                        'ordered_quantity' => $line->quantity,
                        'delivered_quantity' => '0.0000',
                        'unit_price' => $line->unit_price,
                        'subtotal_amount' => $line->total_amount,
                        'discount_value' => '0.0000',
                        'discount_rate' => '0.0000',
                        'discount_amount' => '0.00',
                        'tax_rate' => '0.0000',
                        'tax_amount' => '0.00',
                        'line_total_amount' => $line->total_amount,
                        'total_amount' => $line->total_amount,
                        'created_at' => $now,
                        'updated_at' => $now,
                        'deleted_at' => null,
                    ];
                })->all();
                SalesOrderLine::query()->insert($salesOrderRows);
                $quotation->fill([
                    'status' => QuotationStatus::CONVERTED,
                    'is_converted' => true,
                    'converted_at' => $now,
                ])->save();

                return [
                    'route' => 'sales.sales-orders.show',
                    'document' => $salesOrder->document_number,
                ];
            }

            $invoiceNumber = $this->generateDocumentNumber(LedgerSubType::SALES_INVOICE);

            // Create sales invoice from quotation (for service-only quotations)
            $invoice = SalesInvoice::query()->create([
                'id' => (string) Str::uuid7(),
                'company_id' => $quotation->company_id,
                'contact_id' => $quotation->contact_id,
                'document_number' => $invoiceNumber,
                'sales_type' => SalesType::SERVICE,
                'reference_number' => $quotation->reference_number,
                'sales_order_id' => null,
                'quotation_id' => $quotation->id,
                'company_name' => $quotation->company_name,
                'contact_name' => $quotation->contact?->name,
                'date' => $quotation->getRawOriginal('date'),
                'due_date' => null,
                'status' => LedgerStatus::PENDING,
                'attachment_path' => $quotation->attachment_path,
                'notes' => $quotation->notes,
                'subtotal' => $quotedLines->sum('total_amount'),
                'total_amount' => $quotedLines->sum('total_amount'),
            ]);

            // Create invoice lines from quotation lines
            $invoiceLines = $quotedLines->map(function (QuotationLine $line) use ($invoice, $now): array {
                return [
                    'id' => (string) Str::uuid7(),
                    'sales_invoice_id' => $invoice->id,
                    'product_id' => $line->product_id,
                    'product_name' => $line->product_name,
                    'quantity' => $line->quantity,
                    'unit_price' => $line->unit_price,
                    'subtotal_amount' => $line->total_amount,
                    'discount_rate' => '0.0000',
                    'discount_amount' => '0.00',
                    'tax_rate' => '0.0000',
                    'tax_amount' => '0.00',
                    'tax_mode' => null,
                    'line_total_amount' => $line->total_amount,
                    'total_amount' => $line->total_amount,
                    'description' => $line->description,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            })->all();

            SalesInvoiceLine::query()->insert($invoiceLines);

            $quotation->fill([
                'status' => QuotationStatus::CONVERTED,
                'is_converted' => true,
                'converted_at' => $now,
            ])->save();

            return [
                'route' => 'sales.invoices.show',
                'document' => $invoiceNumber,
            ];
        });

        return redirect()->route($conversion['route'], $conversion['document']);
    }

    /**
     * Find a quotation by its document number, with its lines loaded.
     *
     * @param  string  $quotationNumber  The quotation number value.
     * @return Quotation Returns the matching quotation.
     */
    private function quotationByNumber(string $quotationNumber): Quotation
    {
        /** @var Quotation|null $quotation */
        $quotation = Quotation::query()
            ->with(['company', 'contact', 'lines.product', 'convertedSalesOrder', 'convertedInvoice'])
            ->where('document_number', $quotationNumber)
            ->first();
        abort_if($quotation === null, 404);

        return $quotation;
    }

    /**
     * Build one quotation line from a submitted form row.
     *
     * @param  array<string, mixed>  $line  The submitted line values.
     * @param  int  $index  The position of the line in the form, for error keys.
     * @param  EloquentCollection<string, Product>  $products  The quoted products keyed by id.
     * @return array<string, mixed> Returns the attributes for one quotation line.
     */
    private function lineRow(
        array $line,
        int $index,
        EloquentCollection $products
    ): array {
        if (! filled($line['quantity'] ?? null) || ! filled($line['unit_price'] ?? null)) {
            throw ValidationException::withMessages(["lines.$index.quantity" => __('quotations.messages.line_values_required')]);
        }
        $productId = $line['product_id'];
        /** @var Product|null $product */
        $product = is_string($productId) ? $products->get($productId) : null;
        $quantityRaw = $line['quantity'];
        $quantity = DecimalService::normalize(is_string($quantityRaw) ? $quantityRaw : '');
        $unitPriceRaw = $line['unit_price'];
        $unitPrice = DecimalService::normalize(is_string($unitPriceRaw) ? $unitPriceRaw : '');
        $amount = bcmul($quantity, $unitPrice, 2);

        return [
            'product_id' => $product?->id,
            'product_name' => $product?->name,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total_amount' => $amount,
            'description' => $line['description'] ?? null,
        ];
    }

    /**
     * Authorise quotation access through the existing quotation file permissions.
     *
     * @param  FileAction|null  $action  The requested file action.
     * @return void Aborts with 401 or 403 when the user lacks quotation permissions.
     */
    private function authorizeQuotation(?FileAction $action = null): void
    {
        $user = auth()->user();
        abort_if($user === null, 401);
        $hasQuotationPermission = $action === null
            ? $user->hasFilePermission(FileResources::QUOTATIONS)
            : $user->hasFilePermission(FileResources::QUOTATIONS, $action);
        abort_unless($hasQuotationPermission, 403);
    }

    /**
     * Handle the form data operation.
     *
     * @return array<string, mixed> Returns the resulting data array.
     */
    private function formData(): array
    {
        return [
            'companies' => Company::query()->where('is_debtor', true)->orderBy('name')->get(),
            'contacts' => Contact::query()->whereHas('company', fn ($query) => $query->where('is_debtor', true))->orderBy('name')->get(),
            'products' => Product::query()->orderBy('name')->get(),
            'statuses' => QuotationStatus::cases(),
        ];
    }
}
