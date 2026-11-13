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

use App\Enums\FileResources;
use App\Enums\Finance\DocumentStatus;
use App\Http\Controllers\Controller;
use App\Models\Finance\JournalEntry;
use App\Models\Finance\Ledger;
use App\Models\System\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Class HistoryController
 *
 * Lists, views, reverses, and exports posted journal entries.
 */
class HistoryController extends Controller
{
    /**
     * Display a listing of posted journal entries.
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

        $entries = JournalEntry::query()
            ->with('charts')
            ->where('status', DocumentStatus::POSTED)
            ->when(is_string($search) && $search !== '', function ($q) use ($search) {
                $q->where(function ($sq) use ($search) {
                    $sq->where('document_number', 'like', "%$search%")
                        ->orWhere('reference_number', 'like', "%$search%")
                        ->orWhere('notes', 'like', "%$search%")
                        ->orWhereHas('charts', fn ($iq) => $iq
                            ->where('chart_code', 'like', "%$search%")
                            ->orWhere('chart_name', 'like', "%$search%")
                            ->orWhere('line_description', 'like', "%$search%")
                            ->orWhere('description', 'like', "%$search%"));
                });
            })
            ->when(is_string($startDate) && $startDate !== '', fn ($q) => $q->whereDate('date', '>=', $startDate))
            ->when(is_string($endDate) && $endDate !== '', fn ($q) => $q->whereDate('date', '<=', $endDate))
            ->latest('date')
            ->paginate(self::PER_PAGE_GRID_NORMAL)
            ->withQueryString();

        return view('finance.journal-entries.history.index', compact('entries', 'search', 'startDate', 'endDate'));
    }

    /**
     * Display the specified posted journal entry in read-only mode.
     *
     * @param  string  $documentNumber  The journal entry document number.
     * @return View Returns the rendered Blade view.
     */
    public function show(string $documentNumber): View
    {
        $this->allows(FileResources::JOURNAL_ENTRIES);

        $journalEntry = JournalEntry::query()
            ->where('document_number', $documentNumber)
            ->first();

        abort_if($journalEntry === null, 404);
        abort_if($journalEntry->status !== DocumentStatus::POSTED, 404);

        $journalEntry->load('charts');

        $reversalNumber = JournalEntry::query()
            ->where('document_original', $journalEntry->document_number)
            ->value('document_number');

        /** @var Collection<int, Ledger> $glLedgers */
        $glLedgers = Ledger::query()
            ->where('document_number', $journalEntry->document_number)
            ->orderBy('id')
            ->get();

        return view('finance.journal-entries.history.show', compact('journalEntry', 'reversalNumber', 'glLedgers'));
    }

    /**
     * Stream a PDF for a posted journal entry.
     *
     * @param  string  $documentNumber  The journal entry document number.
     * @return Response The streamed journal entry PDF.
     */
    public function pdf(string $documentNumber): Response
    {
        $this->allows(FileResources::JOURNAL_ENTRIES);

        $journalEntry = JournalEntry::query()
            ->where('document_number', $documentNumber)
            ->first();

        abort_if($journalEntry === null, 404);
        abort_if($journalEntry->status !== DocumentStatus::POSTED, 404);

        $journalEntry->load('charts');
        $setting = Setting::query()->first();

        return Pdf::loadView('finance.journal-entries.pdf', compact('journalEntry', 'setting'))
            ->setPaper('a4')
            ->stream($journalEntry->document_number.'.pdf');
    }
}
