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

namespace App\Http\Controllers\Finance;

use App\Enums\ChartReportGroupEnum;
use App\Enums\FileResources;
use App\Enums\LedgerStatus;
use App\Enums\LedgerSubType;
use App\Enums\LedgerType;
use App\Http\Controllers\Controller;
use App\Models\ChartOfAccount\Chart;
use App\Models\Finance\JournalEntry;
use App\Models\Finance\Ledger;
use App\Models\System\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Provide read-only General Ledger reporting.
 *
 * Ledger rows are immutable accounting history. Corrections must be performed
 * through the originating document workflow or a journal reversal so the audit
 * trail contains both the original posting and its correcting transaction.
 */
class LedgerController extends Controller
{
    /**
     * Build the filtered query for General Ledger reporting.
     *
     * @param  Request  $request  The HTTP request instance.
     * @return Builder<Ledger> Returns the Eloquent query builder.
     */
    private function buildFilteredQuery(Request $request): Builder
    {
        /** @var string|null $search */
        $search = $request->input('search');
        /** @var string|null $startDate */
        $startDate = $request->input('start_date');
        /** @var string|null $endDate */
        $endDate = $request->input('end_date');
        /** @var string|null $type */
        $type = $request->input('type');
        $account = $request->string('account')->trim()->toString();

        $allowedSortColumns = [
            'document_number' => 'document_number',
            'reference_number' => 'reference_number',
            'date' => 'date',
            'type' => 'type',
            'debit_amount' => 'debit_amount',
            'credit_amount' => 'credit_amount',
        ];

        /** @var string|null $sortBy */
        $sortBy = $request->input('sort_by');
        /** @var string $orderInput */
        $orderInput = $request->input('order') ?? '';
        $order = strtolower($orderInput) === 'asc' ? 'asc' : 'desc';

        $query = Ledger::query()
            ->with(['company', 'contact'])
            ->when(is_string($type) && $type !== '', fn ($q) => $q->where('type', $type))
            ->when($account !== '', function (Builder $query) use ($account): void {
                $query->where(function (Builder $query) use ($account): void {
                    $query->where('debit_acc', $account)
                        ->orWhere('credit_acc', $account);
                });
            })
            ->when(
                $request->boolean('posted_only'),
                fn (Builder $query) => $query->where('status', LedgerStatus::POSTED)
            )
            ->when(
                $request->boolean('exclude_year_end_closing'),
                fn (Builder $query) => $query->where('document_number', 'not like', 'YEC%')
            )
            ->when(is_string($search) && $search !== '', function ($q) use ($search) {
                $q->where(function ($sq) use ($search) {
                    $sq->where('reference_number', 'like', "%$search%")
                        ->orWhere('document_number', 'like', "%$search%")
                        ->orWhereHas('company', fn ($cq) => $cq->where('name', 'like', "%$search%"))
                        ->orWhereHas('contact', fn ($ctq) => $ctq->where('name', 'like', "%$search%"));
                });
            })
            ->when(is_string($startDate) && $startDate !== '', fn ($q) => $q->whereDate('date', '>=', $startDate))
            ->when(is_string($endDate) && $endDate !== '', fn ($q) => $q->whereDate('date', '<=', $endDate));

        if ($request->boolean('cash_only')) {
            $cashCharts = Chart::query()
                ->where(function (Builder $query): void {
                    $query->where('isBank', true)
                        ->orWhere('report_group', ChartReportGroupEnum::CASH_AND_CASH_EQUIVALENTS->value);
                })
                ->get(['id', 'code']);
            $cashChartIds = $cashCharts->pluck('id')->filter()->values();
            $cashChartCodes = $cashCharts->pluck('code')->filter()->values();

            $cashDocuments = Ledger::query()
                ->select('document_number')
                ->whereNotNull('document_number')
                ->where('status', LedgerStatus::POSTED)
                ->where(function (Builder $query) use ($cashChartIds, $cashChartCodes): void {
                    $query->whereIn('debit_id', $cashChartIds)
                        ->orWhereIn('credit_id', $cashChartIds)
                        ->orWhereIn('debit_acc', $cashChartCodes)
                        ->orWhereIn('credit_acc', $cashChartCodes);
                });

            $query->whereIn('document_number', $cashDocuments);
        }

        if (is_string($sortBy) && array_key_exists($sortBy, $allowedSortColumns)) {
            $query->orderBy($allowedSortColumns[$sortBy], $order);
        } else {
            $query->latest('date');
        }

        return $query;
    }

    /**
     * Display a listing of the resource.
     * Display the read-only General Ledger.
     *
     * @param  Request  $request  The HTTP request instance.
     * @return View Returns the rendered Blade view.
     */
    public function index(Request $request): View
    {
        $this->allows(FileResources::LEDGERS);
        /** @var string|null $search */
        $search = $request->input('search');
        /** @var string|null $startDate */
        $startDate = $request->input('start_date');
        /** @var string|null $endDate */
        $endDate = $request->input('end_date');
        $account = $request->string('account')->trim()->toString();

        $ledgers = $this->buildFilteredQuery($request)
            ->paginate(self::PER_PAGE_GRID_STANDARD)
            ->withQueryString();
        $sourceUrls = $this->sourceUrls($ledgers->getCollection());

        return view('finance.ledgers.index', compact(
            'ledgers',
            'search',
            'startDate',
            'endDate',
            'account',
            'sourceUrls'
        ));
    }

    /**
     * Resolve each posted row to its originating sub-ledger document when one exists.
     *
     * @param  Collection<int, Ledger>  $ledgers  The posted ledger entries.
     * @return array<string, string> Returns the resolved source URLs keyed by document number.
     */
    private function sourceUrls(Collection $ledgers): array
    {
        $journalEntries = JournalEntry::query()
            ->whereIn(
                'document_number',
                $ledgers
                    ->filter(fn (Ledger $ledger): bool => (
                        $ledger->subtype
                    ) === LedgerSubType::JOURNAL && (
                        $ledger->type
                    ) === LedgerType::JOURNAL)
                    ->pluck('document_number')
                    ->filter()
                    ->unique()
            )
            ->pluck('id', 'document_number');

        return $ledgers
            ->mapWithKeys(function (Ledger $ledger) use ($journalEntries): array {
                $documentNumber = (string) $ledger->document_number;

                if ($documentNumber === '') {
                    return [];
                }

                $subtype = $ledger->subtype;
                $type = $ledger->type;

                $url = match ($subtype) {
                    LedgerSubType::SALES_INVOICE,
                    LedgerSubType::MANUAL_SALES_INVOICE => route(
                        'sales.invoices.show',
                        $documentNumber
                    ),
                    LedgerSubType::PURCHASE_INVOICE => route(
                        'purchase.invoices.show',
                        $documentNumber
                    ),
                    // Receipts and payment vouchers are separate document
                    // families now, so each subtype links into its own grid.
                    LedgerSubType::RECEIPT => route(
                        'bank.receipts.show',
                        $documentNumber
                    ),
                    LedgerSubType::PAYMENT_VOUCHER => route(
                        'bank.payment-vouchers.show',
                        $documentNumber
                    ),
                    LedgerSubType::PURCHASE_DEBIT_NOTE,
                    LedgerSubType::PURCHASE_CREDIT_NOTE => route('purchase.adjustments.show', $documentNumber),
                    LedgerSubType::INCREASE_ADJUSTMENT,
                    LedgerSubType::DECREASE_ADJUSTMENT => route(
                        'sales.adjustments.show',
                        $documentNumber
                    ),
                    // A refund links to the grid for its own kind. The two kinds
                    // are separate document families now, so a supplier refund
                    // linking into the customer refund grid would land on a
                    // document that is not there.
                    LedgerSubType::RECEIPT_REFUND,
                    LedgerSubType::REFUND => route(
                        'bank.customer-refunds.show',
                        $documentNumber
                    ),
                    LedgerSubType::SUPPLIER_REFUND => route(
                        'bank.supplier-refunds.show',
                        $documentNumber
                    ),
                    // A bank journal is a document of its own, so its rows link
                    // into its own grid.
                    LedgerSubType::BANK_JOURNAL => route(
                        'bank.bank-journals.show',
                        $documentNumber
                    ),
                    // A transfer and a withdrawal are movements between money
                    // accounts rather than refunds. They have no document of
                    // their own yet, so there is nothing to link to.
                    LedgerSubType::TRANSFER,
                    LedgerSubType::WITHDRAWAL => null,
                    LedgerSubType::JOURNAL => $type === LedgerType::JOURNAL
                        && filled($journalEntries->get($documentNumber))
                        ? route(
                            'finance.journal-entries.show',
                            $journalEntries->get($documentNumber)
                        )
                        : null,
                    // Stock takes are not available in this edition, so there is no link.
                    LedgerSubType::STOCK_VARIANCE => null,
                };

                return $url === null ? [] : [$ledger->id => $url];
            })
            ->all();
    }

    /**
     * Export the filtered General Ledger to PDF.
     *
     * @param  Request  $request  The HTTP request instance.
     * @return Response Returns the HTTP response.
     */
    public function exportPdf(Request $request): Response
    {
        $this->allows(FileResources::LEDGERS);
        /** @var EloquentCollection<int, Ledger> $ledgers */
        $ledgers = $this->buildFilteredQuery($request)->get();
        /** @var string|null $startDate */
        $startDate = $request->input('start_date');
        /** @var string|null $endDate */
        $endDate = $request->input('end_date');

        $setting = Setting::query()->first();

        return Pdf::loadView('finance.ledgers.pdf', compact('ledgers', 'startDate', 'endDate') + ['setting' => $setting])
            ->setPaper('a4', 'landscape')
            ->download('ledger_report_'.now()->format('Ymd_His').'.pdf');
    }

    /**
     * Export the filtered General Ledger to an Excel workbook.
     *
     * The export uses the same search, type, date, and sorting filters as the
     * on-screen ledger so the downloaded accounting history matches what the
     * user is reviewing.
     *
     * @param  Request  $request  The HTTP request instance.
     * @return StreamedResponse Returns the HTTP response.
     */
    public function exportExcel(Request $request): StreamedResponse
    {
        $this->allows(FileResources::LEDGERS);
        /** @var EloquentCollection<int, Ledger> $ledgers */
        $ledgers = $this->buildFilteredQuery($request)->get();
        $spreadsheet = new Spreadsheet;
        /** @var string $appName */
        $appName = config('app.name');
        $spreadsheet->getProperties()
            ->setCreator($appName)
            ->setTitle(__('ledgers.title'));

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(mb_substr(__('ledgers.title'), 0, 31));
        $headers = [
            __('ledgers.table.column_document_number'),
            __('ledgers.table.column_date'),
            __('ledgers.table.column_type'),
            __('ledgers.table.column_company'),
            __('ledgers.table.column_debit_amount'),
            __('ledgers.table.column_credit_amount'),
        ];

        foreach ($headers as $index => $header) {
            $sheet->setCellValue([$index + 1, 1], $header);
        }

        foreach ($ledgers as $index => $ledger) {
            $row = $index + 2;
            $type = $ledger->type->value;
            $subtype = $ledger->subtype->value;
            $account = trim(implode(' ', array_filter([
                $ledger->debit_acc,
                $ledger->credit_acc,
                $ledger->debit_acc_desc,
                $ledger->credit_acc_desc,
            ], fn ($value): bool => filled($value))));
            $company = $ledger->company_name ?? $ledger->company->name ?? '-';
            $contact = $ledger->contact->name ?? '';

            $sheet->setCellValueExplicit(
                [1, $row],
                trim(implode("\n", array_filter([
                    (string) $ledger->document_number,
                    $ledger->reference_number !== $ledger->document_number
                        ? (string) $ledger->reference_number
                        : null,
                    $account,
                ], fn ($value): bool => filled($value)))),
                DataType::TYPE_STRING
            );
            $sheet->setCellValue([2, $row], $ledger->date->format('d M Y'));
            $sheet->setCellValue([3, $row], __('ledgers.type.'.$type)."\n".__('ledgers.sub_type.'.$subtype));
            $sheet->setCellValue([4, $row], $company."\n".$contact);
            $sheet->setCellValue([5, $row], (float) $ledger->debit_amount > 0 ? (float) $ledger->debit_amount : '-');
            $sheet->setCellValue([6, $row], (float) $ledger->credit_amount > 0 ? (float) $ledger->credit_amount : '-');
        }

        $totalRow = $ledgers->count() + 2;
        $sheet->setCellValue([4, $totalRow], __('common.labels.total'));
        $sumDebit = $ledgers->sum('debit_amount');
        $sumCredit = $ledgers->sum('credit_amount');
        $sheet->setCellValue([5, $totalRow], is_numeric($sumDebit) ? (float) $sumDebit : 0.0);
        $sheet->setCellValue([6, $totalRow], is_numeric($sumCredit) ? (float) $sumCredit : 0.0);
        $sheet->getStyle('A1:F1')->getFont()->setBold(true);
        $sheet->getStyle('A1:F1')->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle('D'.$totalRow.':F'.$totalRow)->getFont()->setBold(true);
        $sheet->getStyle('D'.$totalRow.':F'.$totalRow)->getBorders()->getTop()->setBorderStyle(Border::BORDER_DOUBLE);
        $sheet->getStyle('A2:D'.max(2, $totalRow - 1))->getAlignment()->setWrapText(true);
        $sheet->getStyle('E2:F'.$totalRow)->getNumberFormat()->setFormatCode('#,##0.00;[Red]-#,##0.00');
        $sheet->getStyle('E2:F'.$totalRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:F'.max(1, $totalRow - 1));

        foreach (range('A', 'F') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $filename = 'ledgers_'.now()->format('Ymd_His').'.xlsx';

        return response()->streamDownload(
            function () use ($spreadsheet): void {
                new Xlsx($spreadsheet)->save('php://output');
                $spreadsheet->disconnectWorksheets();
            },
            $filename,
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );
    }
}
