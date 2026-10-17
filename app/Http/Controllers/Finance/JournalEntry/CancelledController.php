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

namespace App\Http\Controllers\Finance\JournalEntry;

use App\Enums\FileAction;
use App\Enums\FileResources;
use App\Enums\LedgerStatus;
use App\Http\Controllers\Controller;
use App\Models\Finance\JournalEntry;
use App\Models\System\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Class CancelledController
 *
 * Lists, views, restores, and exports cancelled journal entries.
 */
class CancelledController extends Controller
{
    /**
     * Build a filtered query for cancelled journal entries.
     *
     * @param  Request  $request  The incoming request containing list filters.
     * @return Builder<JournalEntry> Returns the query builder instance.
     */
    private function buildQuery(Request $request): Builder
    {
        /** @var string|null $search */
        $search = $request->input('search');
        /** @var string|null $startDate */
        $startDate = $request->input('start_date');
        /** @var string|null $endDate */
        $endDate = $request->input('end_date');

        return JournalEntry::query()
            ->with(['charts', 'reversal'])
            ->where('status', LedgerStatus::CANCELLED)
            ->when(is_string($search) && $search !== '', function ($q) use ($search) {
                $q->where(function ($sq) use ($search) {
                    $sq->where('document_number', 'like', "%$search%")
                        ->orWhere('reference_number', 'like', "%$search%")
                        ->orWhere('notes', 'like', "%$search%")
                        ->orWhereHas('charts', fn ($iq) => $iq
                            ->where('debit_acc', 'like', "%$search%")
                            ->orWhere('credit_acc', 'like', "%$search%")
                            ->orWhere('debit_acc_desc', 'like', "%$search%")
                            ->orWhere('credit_acc_desc', 'like', "%$search%")
                            ->orWhere('description', 'like', "%$search%"));
                });
            })
            ->when(is_string($startDate) && $startDate !== '', fn ($q) => $q->whereDate('date', '>=', $startDate))
            ->when(is_string($endDate) && $endDate !== '', fn ($q) => $q->whereDate('date', '<=', $endDate))
            ->latest('date');
    }

    /**
     * Display a listing of cancelled journal entries.
     *
     * @param  Request  $request  The incoming request containing list filters.
     * @return View Returns the rendered Blade view.
     */
    public function index(Request $request): View
    {
        $this->allows(FileResources::JOURNAL_ENTRIES);

        /** @var string|null $search */
        $search = $request->input('search');
        /** @var string|null $startDate */
        $startDate = $request->input('start_date');
        /** @var string|null $endDate */
        $endDate = $request->input('end_date');

        $entries = $this->buildQuery($request)
            ->paginate(self::PER_PAGE_GRID_NORMAL)
            ->withQueryString();

        return view('finance.journal-entries.cancelled.index', compact('entries', 'search', 'startDate', 'endDate'));
    }

    /**
     * Display the specified cancelled journal entry.
     *
     * @param  JournalEntry  $journalEntry  The cancelled journal entry to display.
     * @return View Returns the rendered Blade view.
     */
    public function show(JournalEntry $journalEntry): View
    {
        $this->allows(FileResources::JOURNAL_ENTRIES);

        abort_if($journalEntry->status !== LedgerStatus::CANCELLED, 404);

        $journalEntry->load(['charts', 'reversal', 'reversalOf']);

        return view('finance.journal-entries.cancelled.show', compact('journalEntry'));
    }

    /**
     * Restore a cancelled journal entry to pending status.
     *
     * @param  JournalEntry  $journalEntry  The cancelled journal entry to restore.
     * @return RedirectResponse Redirects to the cancelled journal entries index.
     */
    public function uncancel(JournalEntry $journalEntry): RedirectResponse
    {
        $this->allows(FileResources::JOURNAL_ENTRIES, FileAction::APPROVE);

        abort_if($journalEntry->status !== LedgerStatus::CANCELLED, 404);

        $journalEntry->update(['status' => LedgerStatus::PENDING]);
        $this->attributeUndone($journalEntry);

        return redirect()
            ->route('finance.journal-entries.cancelled.index')
            ->with('success', __('common.messages.uncancelled'));
    }

    /**
     * Export cancelled journal entries to PDF.
     *
     * @param  Request  $request  The incoming request containing list filters.
     * @return Response Returns the PDF download response.
     */
    public function exportPdf(Request $request): Response
    {
        $this->allows(FileResources::JOURNAL_ENTRIES);

        $entries = $this->buildQuery($request)->get();
        /** @var string|null $startDate */
        $startDate = $request->input('start_date');
        /** @var string|null $endDate */
        $endDate = $request->input('end_date');

        $setting = Setting::query()->first();

        return Pdf::loadView('finance.journal-entries.cancelled.pdf', compact('entries', 'startDate', 'endDate') + ['setting' => $setting])
            ->setPaper('a4', 'landscape')
            ->download('cancelled_journal_entries_'.now()->format('Ymd_His').'.pdf');
    }

    /**
     * Export cancelled journal entries to Excel.
     *
     * @param  Request  $request  The incoming request containing list filters.
     * @return StreamedResponse Returns the Excel download response.
     */
    public function exportExcel(Request $request): StreamedResponse
    {
        $this->allows(FileResources::JOURNAL_ENTRIES);

        $entries = $this->buildQuery($request)->get();
        $spreadsheet = new Spreadsheet;
        /** @var string $appName */
        $appName = config('app.name');
        $spreadsheet->getProperties()
            ->setCreator($appName)
            ->setTitle('Cancelled Journal Entries');

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Cancelled Journal Entries');

        $headers = [
            __('journal_entries.table.column_document_number'),
            __('journal_entries.table.column_reference_number'),
            __('journal_entries.table.column_date'),
            __('journal_entries.table.column_notes'),
            __('journal_entries.table.column_debit_amount'),
            __('journal_entries.table.column_credit_amount'),
        ];

        foreach ($headers as $index => $header) {
            $sheet->setCellValue([$index + 1, 1], $header);
        }
        /** @var JournalEntry $entry */
        foreach ($entries as $index => $entry) {
            $row = $index + 2;
            $sheet->setCellValue([1, $row], $entry->document_number);
            $sheet->setCellValue([2, $row], $entry->reference_number ?? '-');
            $sheet->setCellValue([3, $row], Carbon::parse($entry->date)->format('d M Y'));
            $sheet->setCellValue([4, $row], $entry->notes ?? '-');
            $sheet->setCellValue([5, $row], (float) $entry->total_debit);
            $sheet->setCellValue([6, $row], (float) $entry->total_credit);
        }

        $totalRow = $entries->count() + 2;
        $sheet->setCellValue([4, $totalRow], __('common.labels.total'));
        $sumDebit = $entries->sum('total_debit');
        $sumCredit = $entries->sum('total_credit');
        $sheet->setCellValue([5, $totalRow], is_numeric($sumDebit) ? (float) $sumDebit : 0.0);
        $sheet->setCellValue([6, $totalRow], is_numeric($sumCredit) ? (float) $sumCredit : 0.0);
        $sheet->getStyle('A1:F1')->getFont()->setBold(true);
        $sheet->getStyle('A1:F1')->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle('D'.$totalRow.':F'.$totalRow)->getFont()->setBold(true);
        $sheet->getStyle('D'.$totalRow.':F'.$totalRow)->getBorders()->getTop()->setBorderStyle(Border::BORDER_DOUBLE);
        $sheet->getStyle('E2:F'.$totalRow)->getNumberFormat()->setFormatCode('#,##0.00;[Red]-#,##0.00');
        $sheet->getStyle('E2:F'.$totalRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:F'.max(1, $totalRow - 1));

        foreach (range('A', 'F') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $filename = 'cancelled_journal_entries_'.now()->format('Ymd_His').'.xlsx';

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
