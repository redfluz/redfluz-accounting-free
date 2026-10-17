<?php

namespace App\Http\Controllers\Purchase;

use App\Constants\FlashMessage;
use App\Enums\FileAction;
use App\Enums\FileResources;
use App\Enums\ProductMovementDirection;
use App\Enums\ProductMovementSource;
use App\Enums\ProductMovementStatus;
use App\Enums\ProductStockBucket;
use App\Http\Controllers\Controller;
use App\Http\Requests\Purchase\PurchaseReturn\StorePurchaseReturnRequest;
use App\Models\Inventory\Product;
use App\Models\Inventory\ProductLedger;
use App\Services\DecimalService;
use App\Support\Data\Purchase\PurchaseReturnData;
use App\Traits\GeneratesDocumentNumber;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class PurchaseReturnController extends Controller
{
    use GeneratesDocumentNumber;

    /**
     * Display a listing of the resource.
     * Display purchase returns without changing stock or supplier balances.
     *
     * @param  Request  $request  The list filters.
     * @return View The rendered purchase-return list.
     */
    public function index(Request $request): View
    {
        $this->allows(FileResources::PURCHASE_RETURNS);
        $search = $request->string('search')->trim()->toString();
        $startDate = $request->string('start_date')->value();
        $endDate = $request->string('end_date')->value();
        $returns = ProductLedger::query()
            ->where('source_type', ProductMovementSource::PURCHASE_RETURN)
            ->when($search !== '', fn ($query) => $query->where('document_number', 'like', "%$search%"))
            ->when($startDate !== '', fn ($query) => $query->whereDate('date', '>=', $startDate))
            ->when($endDate !== '', fn ($query) => $query->whereDate('date', '<=', $endDate))
            ->latest('date')->paginate(self::PER_PAGE_GRID_NORMAL)->withQueryString();

        $returns->getCollection()->each(function (ProductLedger $return): void {
            $return->setAttribute('is_posted', $return->status === ProductMovementStatus::ACTIVE);
        });

        return view('purchase.returns.index', compact('returns', 'search', 'startDate', 'endDate'));
    }

    /**
     * Show the form for creating a new resource.
     * Show received stock lines available for a purchase return.
     *
     * @return View The rendered purchase-return form.
     */
    public function create(): View
    {
        $this->allows(FileResources::PURCHASE_RETURNS, FileAction::ADD);
        /** @var Collection<int, ProductLedger> $receipts */
        $receipts = ProductLedger::query()
            ->where('source_type', ProductMovementSource::GOODS_RECEIPT)
            ->where('status', ProductMovementStatus::ACTIVE->value)
            ->where('stock_bucket', ProductStockBucket::SALEABLE->value)
            ->whereHas('stockLocation', fn ($query) => $query->where('is_active', true))
            ->latest('date')
            ->get();

        // Group receipts by company → document for cascading filters.
        // Skip entries with no company (e.g. opening stock) — they have no
        // supplier to return to.
        $grouped = $receipts->filter(fn (ProductLedger $row) => ! empty($row->company_name))
            ->groupBy('company_name')->map(
            fn (Collection $companyRows) => $companyRows->groupBy('document_number')->map(
                fn (Collection $docRows) => $docRows->map(fn (ProductLedger $row) => [
                    'id' => $row->id,
                    'product_name' => $row->product_name,
                    'quantity' => $row->quantity,
                    'reference_number' => $row->reference_number,
                ])
            )
        );

        return view('purchase.returns.create', [
            'groupedReceipts' => $grouped,
            'postingDateMin' => null,
            'postingDateMax' => null,
        ]);
    }

    /**
     * Handle the incoming request to create a new resource.
     * Save a pending return against one active GRN line.
     *
     * Pending returns reserve the received quantity. Stock and payable balances
     * do not change until their respective return and credit-note approvals.
     *
     * @param  StorePurchaseReturnRequest  $request  The validated GRN line, returned quantity and date.
     * @return RedirectResponse The return detail page.
     *
     * @throws Throwable When saving or validation fails.
     */
    public function store(StorePurchaseReturnRequest $request): RedirectResponse
    {
        $data = PurchaseReturnData::from($request->validated());

        $documentNumber = DB::transaction(function () use ($data, $request): string {
            $receipt = ProductLedger::query()->whereKey($data->sourceEntryId)
                ->where('source_type', ProductMovementSource::GOODS_RECEIPT)
                ->where('status', ProductMovementStatus::ACTIVE->value)
                ->where('stock_bucket', ProductStockBucket::SALEABLE->value)
                ->lockForUpdate()->firstOrFail();
            $quantity = DecimalService::normalize($data->quantity ?? '');
            $this->validateQuantity($receipt, $quantity);
            if ($request->dateValue() < $receipt->date->toDateString()) {
                throw ValidationException::withMessages(['date' => __('purchase_returns.validation.before_receipt')]);
            }
            $number = $this->generatePurchaseReturnNumber();
            $now = now();
            ProductLedger::query()->insert([[
                'id' => (string) Str::uuid7(),
                'product_id' => $receipt->product_id,
                'stock_location_id' => $receipt->stock_location_id,
                'stock_location_name' => $receipt->stock_location_name,
                'product_sku' => $receipt->product_sku,
                'product_name' => $receipt->product_name,
                'company_id' => $receipt->company_id,
                'company_name' => $receipt->company_name,
                'source_entry_id' => $receipt->id,
                'document_number' => $number,
                'reference_number' => $receipt->reference_number,
                'source_type' => ProductMovementSource::PURCHASE_RETURN->value,
                'direction' => ProductMovementDirection::OUT->value,
                'stock_bucket' => ProductStockBucket::SALEABLE->value,
                'date' => $request->dateValue(),
                'quantity' => $quantity,
                'unit_cost' => $receipt->unit_cost,
                'total_cost' => bcmul($quantity, $receipt->unit_cost, 2),
                'status' => ProductMovementStatus::PENDING->value,
                'notes' => $data->notes,
                'created_at' => $now,
                'updated_at' => $now,
            ]]);

            return $number;
        });

        return redirect()->route('purchase.returns.show', $documentNumber)
            ->with('success', FlashMessage::created(FileResources::PURCHASE_RETURNS));
    }

    /**
     * Display the specified resource.
     * Show the return and its originating GRN line.
     *
     * @param  string  $documentNumber  The return document number.
     * @return View The rendered return details.
     */
    public function show(string $documentNumber): View
    {
        $this->allows(FileResources::PURCHASE_RETURNS);
        $return = ProductLedger::query()->where('document_number', $documentNumber)
            ->where('source_type', ProductMovementSource::PURCHASE_RETURN)->firstOrFail();
        $receipt = ProductLedger::query()->whereKey($return->source_entry_id)->firstOrFail();

        return view('purchase.returns.show', compact('return', 'receipt'));
    }

    /**
     * Post one return as an outgoing stock movement, never a second invoice.
     *
     * Lock the product and receipt, recheck all returns against the received
     * quantity and ensure sufficient current saleable stock. Repeat approval
     * is rejected. No credit note or supplier refund is posted here.
     *
     * @param  string  $documentNumber  The pending return document number.
     * @return RedirectResponse The posted return details.
     *
     * @throws Throwable When the return cannot be posted safely.
     */
    public function approve(string $documentNumber): RedirectResponse
    {
        $this->allows(FileResources::PURCHASE_RETURNS, FileAction::APPROVE);
        DB::transaction(function () use ($documentNumber): void {
            $return = ProductLedger::query()->where('document_number', $documentNumber)
                ->where('source_type', ProductMovementSource::PURCHASE_RETURN)
                ->lockForUpdate()->firstOrFail();
            abort_unless($return->status === ProductMovementStatus::PENDING, 409);
            Product::withTrashed()->whereKey($return->product_id)->lockForUpdate()->firstOrFail();
            $receipt = ProductLedger::query()->whereKey($return->source_entry_id)
                ->where('source_type', ProductMovementSource::GOODS_RECEIPT)
                ->where('status', ProductMovementStatus::ACTIVE->value)
                ->lockForUpdate()->firstOrFail();
            $this->validateQuantity($receipt, $return->quantity, $return->id);
            $balance = ProductLedger::query()->where('product_id', $return->product_id)
                ->where('stock_location_id', $return->stock_location_id)
                ->where('stock_bucket', ProductStockBucket::SALEABLE->value)
                ->where('status', ProductMovementStatus::ACTIVE->value)
                ->selectRaw("COALESCE(SUM(CASE WHEN direction = 'in' THEN quantity ELSE -quantity END), 0) as available")
                ->firstOrFail();
            $availableRaw = $balance->getAttribute('available');
            $available = DecimalService::normalize(is_string($availableRaw) || is_int($availableRaw) || is_float($availableRaw) ? (string) $availableRaw : '0');
            if (bccomp($return->quantity, $available, 4) > 0) {
                throw ValidationException::withMessages(['quantity' => __('purchase_returns.validation.insufficient_stock')]);
            }
            ProductLedger::query()->whereKey($return->id)->update([
                'status' => ProductMovementStatus::ACTIVE->value, 'activated_at' => now(),
            ]);
        });

        return redirect()->route('purchase.returns.show', $documentNumber)
            ->with('success', FlashMessage::approved(FileResources::PURCHASE_RETURNS));
    }

    /**
     * Approve multiple pending purchase returns.
     *
     * @param  Request  $request  The HTTP request instance containing document numbers.
     * @return RedirectResponse Redirects to the specified route.
     *
     * @throws Throwable
     */
    public function batchApprove(Request $request): RedirectResponse
    {
        $this->allows(FileResources::PURCHASE_RETURNS, FileAction::APPROVE);

        $request->validate([
            'document_numbers' => 'required|array|min:1',
            'document_numbers.*' => 'required|string',
        ]);

        $count = 0;

        DB::transaction(function () use ($request, &$count): void {
            /** @var array<int, string> $documentNumbers */
            $documentNumbers = $request->input('document_numbers', []);
            foreach ($documentNumbers as $documentNumber) {
                $return = ProductLedger::query()
                    ->where('document_number', $documentNumber)
                    ->where('source_type', ProductMovementSource::PURCHASE_RETURN)
                    ->lockForUpdate()
                    ->first();

                if ($return === null || $return->status !== ProductMovementStatus::PENDING) {
                    throw ValidationException::withMessages([
                        'document_numbers' => __('common.batch_rejected.not_pending', ['number' => $documentNumber]),
                    ]);
                }

                Product::withTrashed()->whereKey($return->product_id)->lockForUpdate()->firstOrFail();

                $receipt = ProductLedger::query()->whereKey($return->source_entry_id)
                    ->where('source_type', ProductMovementSource::GOODS_RECEIPT)
                    ->where('status', ProductMovementStatus::ACTIVE)
                    ->lockForUpdate()
                    ->first();

                if ($receipt === null) {
                    throw ValidationException::withMessages([
                        'document_numbers' => __('common.batch_rejected.not_found', ['number' => $documentNumber]),
                    ]);
                }

                // Not caught: a quantity that does not fit rejects the batch,
                // the same as any other check failing.
                $this->validateQuantity($receipt, $return->quantity, $return->id);

                $balance = ProductLedger::query()
                    ->where('product_id', $return->product_id)
                    ->where('stock_location_id', $return->stock_location_id)
                    ->where('stock_bucket', ProductStockBucket::SALEABLE)
                    ->where('status', ProductMovementStatus::ACTIVE)
                    ->selectRaw("COALESCE(SUM(CASE WHEN direction = 'in' THEN quantity ELSE -quantity END), 0) as available")
                    ->firstOrFail();

                $availableRaw = $balance->getAttribute('available');
                $available = DecimalService::normalize(is_string($availableRaw) || is_int($availableRaw) || is_float($availableRaw) ? (string) $availableRaw : '0');

                if (bccomp($return->quantity, $available, 4) > 0) {
                    throw ValidationException::withMessages([
                        'document_numbers' => __('common.batch_rejected.non_positive', ['number' => $documentNumber]),
                    ]);
                }

                ProductLedger::query()->whereKey($return->id)->update([
                    'status' => ProductMovementStatus::ACTIVE->value,
                    'activated_at' => now(),
                ]);

                $count++;
            }
        });

        return redirect()
            ->route('purchase.returns.index')
            ->with('success', __('common.messages.batch_approved', ['count' => $count]));
    }

    /**
     * Cancel multiple pending purchase returns.
     *
     * @param  Request  $request  The HTTP request instance containing document numbers.
     * @return RedirectResponse Redirects to the specified route.
     */
    public function batchCancel(Request $request): RedirectResponse
    {
        $this->allows(FileResources::PURCHASE_RETURNS, FileAction::CANCEL);

        $request->validate([
            'document_numbers' => 'required|array|min:1',
            'document_numbers.*' => 'required|string',
        ]);

        $affected = ProductLedger::query()
            ->where('source_type', ProductMovementSource::PURCHASE_RETURN)
            ->whereIn('document_number', $request->input('document_numbers', []))
            ->where('status', ProductMovementStatus::PENDING)
            ->update(['status' => ProductMovementStatus::CANCELLED]);

        return redirect()
            ->route('purchase.returns.index')
            ->with('success', __('common.messages.batch_cancelled', ['count' => $affected]));
    }

    /**
     * Remove a resource by its object.
     * Remove a pending return and release its reserved receipt quantity.
     *
     * @param  string  $documentNumber  The return document number.
     * @return RedirectResponse The return list.
     *
     * @throws Throwable When the return has already been posted.
     */
    public function destroy(string $documentNumber): RedirectResponse
    {
        $this->allows(FileResources::PURCHASE_RETURNS, FileAction::REMOVE);
        DB::transaction(function () use ($documentNumber): void {
            $return = ProductLedger::query()->where('document_number', $documentNumber)
                ->where('source_type', ProductMovementSource::PURCHASE_RETURN)
                ->lockForUpdate()->firstOrFail();
            abort_unless($return->status === ProductMovementStatus::PENDING, 409);
            ProductLedger::query()->whereKey($return->id)->delete();
        });

        return redirect()->route('purchase.returns.index');
    }

    /**
     * Prevent all pending and posted returns exceeding the received quantity.
     *
     * @param  ProductLedger  $receipt  The locked original receipt line.
     * @param  numeric-string  $quantity  The requested return quantity.
     * @param  string|null  $excludeId  The return being approved.
     * @return void Returns nothing.
     *
     * @throws ValidationException When the remaining receipt quantity is exceeded.
     */
    private function validateQuantity(ProductLedger $receipt, string $quantity, ?string $excludeId = null): void
    {
        $reserved = ProductLedger::query()->where('source_type', ProductMovementSource::PURCHASE_RETURN)
            ->where('source_entry_id', $receipt->id)
            ->whereIn('status', [ProductMovementStatus::PENDING->value, ProductMovementStatus::ACTIVE->value])
            ->when($excludeId !== null, fn ($query) => $query->where('id', '!=', $excludeId))
            ->sum('quantity');
        $remaining = bcsub($receipt->quantity, DecimalService::normalize((string) $reserved), 4);
        if (bccomp($quantity, $remaining, 4) > 0) {
            throw ValidationException::withMessages(['quantity' => __('purchase_returns.validation.exceeds_received', ['quantity' => $remaining])]);
        }
    }
}
