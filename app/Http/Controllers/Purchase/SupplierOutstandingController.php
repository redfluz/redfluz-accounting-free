<?php

/**
 * ============================================================================
 *
 * @author      redfluz
 * @copyright   Copyright (c) 2026 redfluz. All Rights Reserved.
 * ============================================================================
 *
 * PROPRIETARY AND CONFIDENTIAL
 */

namespace App\Http\Controllers\Purchase;

use App\Enums\FileResources;
use App\Enums\LedgerStatus;
use App\Enums\ProductMovementDirection;
use App\Enums\ProductMovementSource;
use App\Enums\ProductMovementStatus;
use App\Enums\PurchaseOrderType;
use App\Http\Controllers\Controller;
use App\Models\Bank\PaymentVoucher;
use App\Models\Inventory\ProductLedger;
use App\Models\Purchase\PurchaseInvoice;
use App\Models\Purchase\PurchaseOrderLine;
use App\Models\System\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Display supplier invoice balances using linked payment references. */
class SupplierOutstandingController extends Controller
{
    /**
     * Display posted supplier invoices with their allocated balance and due status.
     *
     * @param  Request  $request  The HTTP request instance containing invoice filters.
     * @return View|Response|StreamedResponse Returns the rendered supplier-outstanding view or export download.
     */
    public function index(Request $request): View|Response|StreamedResponse
    {
        $this->allows(FileResources::SUPPLIER_OUTSTANDING);
        /** @var array<string, mixed> $validated */
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'as_of_date' => ['nullable', 'date'],
            'status' => ['nullable', 'in:outstanding,overdue,due_soon'],
            'export' => ['nullable', 'in:excel,pdf'],
        ]);

        $search = trim(is_string($validated['search'] ?? null) ? (string) $validated['search'] : '');
        /** @var string|null $startDate */
        $startDate = $validated['start_date'] ?? null;
        /** @var string|null $endDate */
        $endDate = $validated['end_date'] ?? null;
        $asOfDateRaw = $validated['as_of_date'] ?? null;
        $asOfDate = Carbon::parse(is_string($asOfDateRaw) ? $asOfDateRaw : today()->toDateString())->startOfDay();
        /** @var string $status */
        $status = $validated['status'] ?? 'outstanding';
        $orderedQuantities = PurchaseOrderLine::query()
            ->from('purchase_order_lines as order_lines')
            ->join('purchase_orders as orders', 'orders.id', '=', 'order_lines.purchase_order_id')
            ->where('orders.purchase_type', PurchaseOrderType::PURCHASE->value)
            ->selectRaw(
                'orders.document_number as reference_number, '
                .'SUM(order_lines.quantity) as ordered_quantity'
            )
            ->groupBy('orders.document_number');
        $receivedQuantities = ProductLedger::query()
            ->selectRaw('reference_number, SUM(quantity) as received_quantity')
            ->where('source_type', ProductMovementSource::GOODS_RECEIPT)
            ->where('direction', ProductMovementDirection::IN->value)
            ->where('status', ProductMovementStatus::ACTIVE->value)
            ->whereNotNull('reference_number')
            ->groupBy('reference_number');
        $paymentTotals = PaymentVoucher::query()
            ->selectRaw('reference_number, SUM(total_amount) as paid_amount')
            ->where('status', LedgerStatus::POSTED)
            ->whereNotNull('reference_number')
            ->groupBy('reference_number');

        $invoiceQuery = PurchaseInvoice::query()
            ->from('purchase_invoices as invoices')
            ->leftJoinSub($paymentTotals, 'payment_totals', function ($join) {
                /** @var JoinClause $join */
                $join->on('payment_totals.reference_number', '=', 'invoices.document_number');
            })
            ->leftJoinSub($orderedQuantities, 'ordered_quantities', function ($join) {
                /** @var JoinClause $join */
                $join->on('ordered_quantities.reference_number', '=', 'invoices.reference_number');
            })
            ->leftJoinSub($receivedQuantities, 'received_quantities', function ($join) {
                /** @var JoinClause $join */
                $join->on('received_quantities.reference_number', '=', 'invoices.reference_number');
            })
            ->selectRaw(
                'invoices.id, invoices.document_number as document_number, '
                .'invoices.company_name, '
                .'invoices.date as invoice_date, '
                .'invoices.due_date, '
                .'invoices.total_amount as invoice_amount, '
                .'COALESCE(payment_totals.paid_amount, 0) as paid_amount, '
                .'COALESCE(ordered_quantities.ordered_quantity, 0) as ordered_quantity, '
                .'COALESCE(received_quantities.received_quantity, 0) as received_quantity'
            )
            ->where('invoices.status', LedgerStatus::POSTED)
            ->where('invoices.purchase_type', PurchaseOrderType::PURCHASE->value)
            ->when($search !== '', fn ($query) => $query->where(function ($subQuery) use ($search): void {
                $subQuery->where('invoices.document_number', 'like', "%$search%")
                    ->orWhere('invoices.company_name', 'like', "%$search%");
            }))
            ->when($startDate !== null, fn ($query) => $query->whereDate('invoices.date', '>=', (string) $startDate))
            ->when($endDate !== null, fn ($query) => $query->whereDate('invoices.date', '<=', (string) $endDate))
            ->whereRaw('invoices.total_amount - COALESCE(payment_totals.paid_amount, 0) > 0')
            ->when($status === 'overdue', fn ($query) => $query->whereRaw('invoices.due_date < ?', [$asOfDate->toDateString()]))
            ->when($status === 'due_soon', fn ($query) => $query->whereRaw('invoices.due_date BETWEEN ? AND ?', [
                $asOfDate->toDateString(),
                $asOfDate->copy()->addDays(7)->toDateString(),
            ]))
            ->orderByRaw('invoices.due_date IS NULL, invoices.due_date')
            ->orderByDesc('invoice_date');

        if ($request->input('export') === 'excel' || $request->input('export') === 'pdf') {
            $exportInvoices = $invoiceQuery->get();
            $this->decorateInvoices($exportInvoices, $asOfDate);
            $setting = Setting::query()->first();

            return $request->input('export') === 'pdf'
                ? Pdf::loadView('purchase.supplier-outstanding.pdf', ['setting' => $setting, ...compact('exportInvoices', 'asOfDate')])->setPaper('a4', 'landscape')->download('supplier-outstanding-'.now()->format('Ymd_His').'.pdf')
                : $this->excel($exportInvoices);
        }

        $allInvoices = $invoiceQuery->get();
        $this->decorateInvoices($allInvoices, $asOfDate);
        $invoices = $invoiceQuery->paginate(self::PER_PAGE_GRID_NORMAL)->withQueryString();

        $this->decorateInvoices($invoices->getCollection(), $asOfDate);
        $totals = $this->totals($allInvoices);

        return view('purchase.supplier-outstanding.index', compact(
            'invoices',
            'search',
            'startDate',
            'endDate',
            'asOfDate',
            'status', 'totals'
        ));
    }

    /**
     * Export the supplier outstanding report as an Excel file.
     *
     * @param  Request  $request  The HTTP request instance.
     * @return View|Response|StreamedResponse Returns the streamed Excel download.
     */
    public function exportExcel(Request $request): View|Response|StreamedResponse
    {
        $request->merge(['export' => 'excel']);

        return $this->index($request);
    }

    /**
     * Export the supplier outstanding report as a PDF file.
     *
     * @param  Request  $request  The HTTP request instance.
     * @return View|Response|StreamedResponse Returns the streamed PDF download.
     */
    public function exportPdf(Request $request): View|Response|StreamedResponse
    {
        $request->merge(['export' => 'pdf']);

        return $this->index($request);
    }

    /**
     * @param  Collection<int, PurchaseInvoice>  $invoices  The invoices collection.
     * @param  Carbon  $asOfDate  The date used for overdue and balance calculations.
     * @return void Returns nothing.
     */
    private function decorateInvoices(Collection $invoices, Carbon $asOfDate): void
    {
        $invoices->each(function (PurchaseInvoice $invoice) use ($asOfDate): void {
            $invoiceAmountRaw = $invoice->getAttribute('invoice_amount');
            $paidAmountRaw = $invoice->getAttribute('paid_amount');
            $invoiceAmount = is_numeric($invoiceAmountRaw) ? (float) $invoiceAmountRaw : 0.0;
            $paidAmount = is_numeric($paidAmountRaw) ? (float) $paidAmountRaw : 0.0;
            $invoice->setAttribute('balance_amount', max(0, $invoiceAmount - $paidAmount));
            $invoice->setAttribute('days_overdue', $invoice->due_date !== null && $invoice->due_date->lt($asOfDate) ? $invoice->due_date->diffInDays($asOfDate) : 0);
            $orderedRaw = $invoice->getAttribute('ordered_quantity');
            $receivedRaw = $invoice->getAttribute('received_quantity');
            $ordered = is_numeric($orderedRaw) ? (float) $orderedRaw : 0.0;
            $received = is_numeric($receivedRaw) ? (float) $receivedRaw : 0.0;
            $invoice->setAttribute('receipt_completion', $ordered > 0 ? min(100, round($received / $ordered * 100, 1)) : null);
        });
    }

    /**
     * @param  Collection<int, PurchaseInvoice>  $invoices  The invoices collection.
     * @return array<string, float|int>
     */
    private function totals(Collection $invoices): array
    {
        $invoiceAmount = $invoices->sum('invoice_amount');
        $paidAmount = $invoices->sum('paid_amount');
        $balanceAmount = $invoices->sum('balance_amount');
        $orderedQuantity = $invoices->sum('ordered_quantity');
        $receivedQuantity = $invoices->sum('received_quantity');

        return [
            'invoice_amount' => is_numeric($invoiceAmount) ? (float) $invoiceAmount : 0.0,
            'paid_amount' => is_numeric($paidAmount) ? (float) $paidAmount : 0.0,
            'balance_amount' => is_numeric($balanceAmount) ? (float) $balanceAmount : 0.0,
            'ordered_quantity' => is_numeric($orderedQuantity) ? (float) $orderedQuantity : 0.0,
            'received_quantity' => is_numeric($receivedQuantity) ? (float) $receivedQuantity : 0.0,
        ];
    }

    /**
     * @param  Collection<int, PurchaseInvoice>  $invoices  The invoices collection to export.
     * @return StreamedResponse Returns the streamed Excel response.
     */
    private function excel(Collection $invoices): StreamedResponse
    {
        $book = new Spreadsheet;
        $sheet = $book->getActiveSheet();
        $headers = ['Reference', 'Supplier', 'Items Received', 'Receipt Completion', 'Invoice Date', 'Credit Term', 'Payment Due', 'Invoice Amount', 'Allocated Paid', 'Outstanding Balance', 'Status'];
        foreach ($headers as $i => $header) {
            $sheet->setCellValue([$i + 1, 1], $header);
        }
        foreach ($invoices as $i => $invoice) {
            $row = $i + 2;
            $receivedQty = $invoice->getAttribute('received_quantity');
            $orderedQty = $invoice->getAttribute('ordered_quantity');
            $receiptCompletion = $invoice->getAttribute('receipt_completion');
            $invoiceDateRaw = $invoice->getAttribute('invoice_date');
            $invoiceDate = $invoiceDateRaw instanceof Carbon ? $invoiceDateRaw : null;
            $balanceAmount = $invoice->getAttribute('balance_amount');
            $invAmountRaw = $invoice->getAttribute('invoice_amount');
            $paidAmtRaw = $invoice->getAttribute('paid_amount');
            $invAmount = is_numeric($invAmountRaw) ? (float) $invAmountRaw : 0.0;
            $paidAmt = is_numeric($paidAmtRaw) ? (float) $paidAmtRaw : 0.0;
            $balanceAmt = is_numeric($balanceAmount) ? (float) $balanceAmount : 0.0;
            $receiptCompletionStr = $receiptCompletion === null ? '-' : (is_numeric($receiptCompletion) ? (string) $receiptCompletion : '').'%';
            $invoiceDateStr = $invoiceDate ? $invoiceDate->format('d/m/Y') : '';
            $daysStr = ($invoice->due_date && $invoiceDate) ? $invoiceDate->diffInDays(Carbon::parse($invoice->due_date)).' days' : '-';
            $dueDateStr = $invoice->due_date ? $invoice->due_date->format('d/m/Y') : '';
            $receivedQtyStr = is_numeric($receivedQty) ? (string) $receivedQty : '';
            $orderedQtyStr = is_numeric($orderedQty) ? (string) $orderedQty : '';
            $documentNumberRaw = $invoice->getAttribute('document_number');
            $documentNumber = is_string($documentNumberRaw) ? $documentNumberRaw : '';
            foreach ([$documentNumber, $invoice->company_name, $receivedQtyStr.'/'.$orderedQtyStr, $receiptCompletionStr, $invoiceDateStr, $daysStr, $dueDateStr, $invAmount, $paidAmt, $balanceAmt, 'Outstanding'] as $j => $value) {
                $sheet->setCellValue([$j + 1, $row], $value);
            }
        }
        $totalRow = $invoices->count() + 2;
        $sheet->setCellValue([1, $totalRow], 'Grand Total');
        $totals = $this->totals($invoices);
        foreach ([3 => $totals['received_quantity'].'/'.$totals['ordered_quantity'], 8 => $totals['invoice_amount'], 9 => $totals['paid_amount'], 10 => $totals['balance_amount']] as $col => $value) {
            $sheet->setCellValue([$col, $totalRow], $value);
        }
        $sheet->getStyle('A1:K1')->getFont()->setBold(true);
        $sheet->getStyle('A'.$totalRow.':K'.$totalRow)->getFont()->setBold(true);
        foreach (range('A', 'K') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        return response()->streamDownload(static function () use ($book): void {
            new Xlsx($book)->save('php://output');
            $book->disconnectWorksheets();
        }, 'supplier-outstanding-'.now()->format('Ymd_His').'.xlsx');
    }
}
