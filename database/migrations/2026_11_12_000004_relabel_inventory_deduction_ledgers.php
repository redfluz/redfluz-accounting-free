<?php

use App\Enums\Finance\LedgerSubType;
use App\Enums\Finance\LedgerType;
use App\Models\Finance\Ledger;
use App\Models\Inventory\ProductLedger;
use App\Traits\GeneratesDocumentNumber;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    use GeneratesDocumentNumber;

    /**
     * Relabel COGS ledger rows as inventory deductions.
     *
     * Automatic stock-out postings are inventory movements sourced from an
     * invoice or delivery order, not manual journal entries. The rows were
     * previously left with journal values and the source document's own
     * number, so each source document's deduction rows are relabelled and
     * given a deduction number of their own, keeping the source document in
     * reference_number.
     */
    public function up(): void
    {
        $ledgerIds = $this->deductionLedgerIds();

        if ($ledgerIds === []) {
            return;
        }

        Ledger::withTrashed()
            ->whereIn('id', $ledgerIds)
            ->update([
                'type' => LedgerType::INVENTORY->value,
                'subtype' => LedgerSubType::INVENTORY_DEDUCTION->value,
            ]);

        $sourceNumbers = Ledger::withTrashed()
            ->whereIn('id', $ledgerIds)
            ->where('subtype', LedgerSubType::INVENTORY_DEDUCTION->value)
            ->distinct()
            ->pluck('document_number');

        foreach ($sourceNumbers as $sourceNumber) {
            if (! is_string($sourceNumber) || $sourceNumber === '') {
                continue;
            }

            $deductionNumber = $this->generateDocumentNumber(LedgerSubType::INVENTORY_DEDUCTION);

            Ledger::withTrashed()
                ->whereIn('id', $ledgerIds)
                ->where('document_number', $sourceNumber)
                ->update([
                    'document_number' => $deductionNumber,
                    'reference_number' => $sourceNumber,
                ]);
        }
    }

    /**
     * Reverse the relabel.
     */
    public function down(): void
    {
        $ledgerIds = $this->deductionLedgerIds();

        if ($ledgerIds === []) {
            return;
        }

        Ledger::withTrashed()
            ->whereIn('id', $ledgerIds)
            ->update([
                'type' => LedgerType::JOURNAL->value,
                'subtype' => LedgerSubType::JOURNAL->value,
            ]);
    }

    /**
     * Collect the ledger ids written by automatic stock-out postings.
     *
     * @return list<string> The COGS and inventory ledger ids, unique.
     */
    private function deductionLedgerIds(): array
    {
        return ProductLedger::withTrashed()
            ->whereNotNull('cogs_ledger_id')
            ->pluck('cogs_ledger_id')
            ->merge(
                ProductLedger::withTrashed()
                    ->whereNotNull('inventory_ledger_id')
                    ->pluck('inventory_ledger_id')
            )
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
};
