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

namespace App\Http\Controllers\Sales;

use App\Enums\FileResources;
use App\Enums\LedgerSubType;
use App\Enums\LedgerType;
use App\Enums\ProductMovementDirection;
use App\Enums\ProductMovementSource;
use App\Enums\ProductMovementStatus;
use App\Http\Controllers\Controller;
use App\Models\Crm\ArLedger;
use App\Models\Inventory\ProductLedger;
use App\Models\Sales\SalesInvoice;
use App\Models\Sales\SalesOrderLine;
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

/** Display customer invoice balances using linked payment references. */
class CustomerOutstandingController extends Controller
{
    /**
     * Display posted supplier invoices with their allocated balance and due status.
     *
     * @param  Request  $request  The HTTP request instance containing invoice filters.
     * @return View|Response|StreamedResponse Returns the rendered customer-outstanding view or export download.
     */
    public function index(Request $request): View|Response|StreamedResponse
    {
        $this->allows(FileResources::SALES_CUSTOMER_OUTSTANDING);
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
        $orderedQuantities = SalesOrderLine::query()
            ->from('sales_order_lines as order_lines')
            ->join('sales_orders as orders', 'orders.id', '=', 'order_lines.sales_order_id')
            ->selectRaw(
                'orders.document_number as reference_number, '
                .'SUM(order_lines.ordered_quantity) as ordered_quantity'
            )
            ->groupBy('orders.document_number');
        $receivedQuantities = ProductLedger::query()
            ->selectRaw('reference_number, SUM(quantity) as delivered_quantity')
            ->where('source_type', ProductMovementSource::DELIVERY_ORDER)
            ->where('direction', ProductMovementDirection::OUT->value)
            ->where('status', ProductMovementStatus::ACTIVE->value)
            ->whereNotNull('reference_number')
            ->groupBy('reference_number');
        $paymentTotals = ArLedger::query()
            ->selectRaw('reference_number, SUM(debit_amount) as paid_amount')
            ->where('type', LedgerType::PAYMENT->value)
            ->where('subtype', LedgerSubType::RECEIPT->value)
            ->whereNotNull('reference_number')
            ->groupBy('reference_number');

        $invoiceQuery = SalesInvoice::query()
            ->from('sales_invoices as invoices')
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
                'invoices.id, invoices.document_number, '
                .'invoices.company_name, invoices.date as invoice_date, '
                .'invoices.due_date, '
                .'invoices.total_amount as invoice_amount, '
                .'COALESCE(payment_totals.paid_amount, 0) as paid_amount, '
                .'COALESCE(ordered_quantities.ordered_quantity, 0) as ordered_quantity, '
                .'COALESCE(received_quantities.delivered_quantity, 0) as delivered_quantity'
            )
            ->where('invoices.status', 'posted')
            ->when($search !== '', fn ($query) => $query->where(function ($subQuery) use ($search): void {
                $subQuery->where('invoices.document_number', 'like', "%$search%")
                    ->orWhere('invoices.company_name', 'like', "%$search%");
            }))
            ->when($startDate !== null, fn ($query) => $query->whereDate('invoices.date', '>=', (string) $startDate))
            ->when($endDate !== null, fn ($query) => $query->whereDate('invoices.date', '<=', (string) $endDate))
            ->havingRaw('invoices.total_amount - COALESCE(payment_totals.paid_amount, 0) > 0')
            ->when($status === 'overdue', fn ($query) => $query->where('invoices.due_date', '<', $asOfDate->toDateString()))
            ->when($status === 'due_soon', fn ($query) => $query->whereBetween('invoices.due_date', [
                $asOfDate->toDateString(),
                $asOfDate->copy()->addDays(7)->toDateString(),
            ]))
            ->orderByRaw('invoices.due_date IS NULL, invoices.due_date')
            ->orderByDesc('invoices.date');

        if ($request->input('export') === 'excel' || $request->input('export') === 'pdf') {
            $exportInvoices = $invoiceQuery->get();
            $this->decorateInvoices($exportInvoices, $asOfDate);
            $setting = Setting::query()->first();

            return $request->input('export') === 'pdf'
                ? Pdf::loadView('sales.customer-outstanding.pdf', ['setting' => $setting, ...compact('exportInvoices', 'asOfDate')])->setPaper('a4', 'landscape')->download('customer-outstanding-'.now()->format('Ymd_His').'.pdf')
                : $this->excel($exportInvoices);
        }

        $allInvoices = $invoiceQuery->get();
        $this->decorateInvoices($allInvoices, $asOfDate);
        $invoices = $invoiceQuery->paginate(self::PER_PAGE_GRID_NORMAL)->withQueryString();

        $this->decorateInvoices($invoices->getCollection(), $asOfDate);
        $totals = $this->totals($allInvoices);

        return view('sales.customer-outstanding.index', compact(
            'invoices',
            'search',
            'startDate',
            'endDate',
            'asOfDate',
            'status', 'totals'
        ));
    }

    /**
     * Export the customer outstanding report as an Excel file.
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
     * Export the customer outstanding report as a PDF file.
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
     * @param  Collection<int, SalesInvoice>  $invoices  The invoices collection.
     * @param  Carbon  $asOfDate  The date used for overdue and balance calculations.
     * @return void Returns nothing.
     */
    private function decorateInvoices(Collection $invoices, Carbon $asOfDate): void
    {
        $invoices->each(function (SalesInvoice $invoice) use ($asOfDate): void {
            $totalRaw = $invoice->getAttribute('total_amount');
            $invoiceAmount = is_numeric($totalRaw) ? (float) $totalRaw : 0.0;
            $paidRaw = $invoice->getAttribute('paid_amount');
            $paidAmount = is_numeric($paidRaw) ? (float) $paidRaw : 0.0;
            $invoice->setAttribute('balance_amount', max(0, $invoiceAmount - $paidAmount));
            $dueDate = $invoice->due_date;
            $invoice->setAttribute('days_overdue', $dueDate instanceof Carbon && $dueDate->lt($asOfDate) ? $dueDate->diffInDays($asOfDate) : 0);
            $orderedRaw = $invoice->getAttribute('ordered_quantity');
            $ordered = is_numeric($orderedRaw) ? (float) $orderedRaw : 0.0;
            $receivedRaw = $invoice->getAttribute('delivered_quantity');
            $received = is_numeric($receivedRaw) ? (float) $receivedRaw : 0.0;
            $invoice->setAttribute('delivery_completion', $ordered > 0 ? min(100, round($received / $ordered * 100, 1)) : null);
        });
    }

    /**
     * @param  Collection<int, SalesInvoice>  $invoices  The invoices collection.
     * @return array{invoice_amount: float, paid_amount: float, balance_amount: float, ordered_quantity: float, delivered_quantity: float}
     */
    private function totals(Collection $invoices): array
    {
        $invoiceRaw = $invoices->sum('total_amount');
        $paidRaw = $invoices->sum(fn (SalesInvoice $inv): mixed => $inv->getAttribute('paid_amount'));
        $balanceRaw = $invoices->sum(fn (SalesInvoice $inv): mixed => $inv->getAttribute('balance_amount'));
        $orderedRaw = $invoices->sum(fn (SalesInvoice $inv): mixed => $inv->getAttribute('ordered_quantity'));
        $deliveredRaw = $invoices->sum(fn (SalesInvoice $inv): mixed => $inv->getAttribute('delivered_quantity'));

        return [
            'invoice_amount' => is_numeric($invoiceRaw) ? (float) $invoiceRaw : 0.0,
            'paid_amount' => is_numeric($paidRaw) ? (float) $paidRaw : 0.0,
            'balance_amount' => is_numeric($balanceRaw) ? (float) $balanceRaw : 0.0,
            'ordered_quantity' => is_numeric($orderedRaw) ? (float) $orderedRaw : 0.0,
            'delivered_quantity' => is_numeric($deliveredRaw) ? (float) $deliveredRaw : 0.0,
        ];
    }

    /**
     * @param  Collection<int, SalesInvoice>  $invoices  The invoices collection to export.
     * @return StreamedResponse Returns the streamed Excel response.
     */
    private function excel(Collection $invoices): StreamedResponse
    {
        $book = new Spreadsheet;
        $sheet = $book->getActiveSheet();
        $headers = ['Reference', 'Customer', 'Items Delivered', 'Delivery Completion', 'Invoice Date', 'Credit Term', 'Payment Due', 'Invoice Amount', 'Allocated Paid', 'Outstanding Balance', 'Status'];
        foreach ($headers as $i => $header) {
            $sheet->setCellValue([$i + 1, 1], $header);
        }
        foreach ($invoices as $i => $invoice) {
            $row = $i + 2;
            $deliveredRaw = $invoice->getAttribute('delivered_quantity');
            $deliveredQty = is_numeric($deliveredRaw) ? (string) $deliveredRaw : '';
            $orderedQtyRaw = $invoice->getAttribute('ordered_quantity');
            $orderedQty = is_numeric($orderedQtyRaw) ? (string) $orderedQtyRaw : '';
            $completionRaw = $invoice->getAttribute('delivery_completion');
            $deliveryCompletionStr = is_numeric($completionRaw) ? (string) $completionRaw.'%' : '-';
            $invoiceDate = $invoice->date;
            $invoiceDateStr = $invoiceDate instanceof Carbon ? $invoiceDate->format('d/m/Y') : '-';
            $dueDate = $invoice->due_date;
            $daysDiff = ($dueDate instanceof Carbon && $invoiceDate instanceof Carbon) ? $invoiceDate->diffInDays($dueDate).' days' : '-';
            $dueDateStr = $dueDate instanceof Carbon ? $dueDate->format('d/m/Y') : '-';
            $totalRaw = $invoice->getAttribute('total_amount');
            $invoiceAmount = is_numeric($totalRaw) ? (float) $totalRaw : 0.0;
            $paidExcelRaw = $invoice->getAttribute('paid_amount');
            $paidAmount = is_numeric($paidExcelRaw) ? (float) $paidExcelRaw : 0.0;
            $balanceExcelRaw = $invoice->getAttribute('balance_amount');
            $balanceAmount = is_numeric($balanceExcelRaw) ? (float) $balanceExcelRaw : 0.0;
            foreach ([$invoice->document_number, $invoice->company_name, $deliveredQty.'/'.$orderedQty, $deliveryCompletionStr, $invoiceDateStr, $daysDiff, $dueDateStr, $invoiceAmount, $paidAmount, $balanceAmount, 'Outstanding'] as $j => $value) {
                $sheet->setCellValue([$j + 1, $row], $value);
            }
        }
        $totalRow = $invoices->count() + 2;
        $sheet->setCellValue([1, $totalRow], 'Grand Total');
        $totals = $this->totals($invoices);
        foreach ([3 => $totals['delivered_quantity'].'/'.$totals['ordered_quantity'], 8 => $totals['invoice_amount'], 9 => $totals['paid_amount'], 10 => $totals['balance_amount']] as $col => $value) {
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
        }, 'customer-outstanding-'.now()->format('Ymd_His').'.xlsx');
    }
}
