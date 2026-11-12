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

namespace app\Http\Controllers\Finance\JournalEntry;

use App\Enums\FileAction;
use App\Enums\FileResources;
use App\Enums\Finance\DocumentStatus;
use App\Http\Controllers\Controller;
use App\Models\Finance\JournalEntry;
use App\Models\System\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Class CancelledController
 *
 * Lists, views, restores, and exports cancelled journal entries.
 */
class CancelledController extends Controller
{
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

        $entries = JournalEntry::query()
            ->with('charts')
            ->where('status', DocumentStatus::CANCELLED)
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

        abort_if($journalEntry->status !== DocumentStatus::CANCELLED, 404);

        $journalEntry->load('charts');

        return view('finance.journal-entries.cancelled.show', compact('journalEntry'));
    }

    /**
     * Stream a PDF for a cancelled journal entry.
     *
     * @param  JournalEntry  $journalEntry  The cancelled journal entry to render.
     * @return Response The streamed journal entry PDF.
     */
    public function pdf(JournalEntry $journalEntry): Response
    {
        $this->allows(FileResources::JOURNAL_ENTRIES);

        abort_if($journalEntry->status !== DocumentStatus::CANCELLED, 404);

        $journalEntry->load('charts');
        $setting = Setting::query()->first();

        return Pdf::loadView('finance.journal-entries.pdf', compact('journalEntry', 'setting'))
            ->setPaper('a4')
            ->stream($journalEntry->document_number.'.pdf');
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

        abort_if($journalEntry->status !== DocumentStatus::CANCELLED, 404);

        $journalEntry->update(['status' => DocumentStatus::PENDING]);
        $this->attributeUndone($journalEntry);

        return redirect()
            ->route('finance.journal-entries.cancelled.index')
            ->with('success', __('common.messages.uncancelled'));
    }
}
