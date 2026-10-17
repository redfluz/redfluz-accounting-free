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

namespace Database\Seeders\Concerns;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Seeds document number sequences without ever rewinding one.
 *
 * A document number is a prefix plus a running counter, and the number is
 * protected by a unique index that covers soft deleted rows as well, because a
 * number that has been issued must never be handed out again. A seeder that
 * rewrites the counter back to its own starting value therefore breaks the next
 * document created through the UI: the counter lands on a number that already
 * exists, and the insert fails with a unique constraint violation.
 *
 * Re-running the seeders is normal, so the counter is advanced past every number
 * that has already been issued instead of being reset.
 */
trait SeedsDocumentSequences
{
    /**
     * Create or advance a document number sequence.
     *
     * The stored counter is kept stable so repeated seeding is idempotent, and is
     * advanced past the highest number already issued for the prefix so a fresh
     * database and a used one both produce the next unused number.
     *
     * @param  string  $type  The sequence type, e.g. purchase_order.
     * @param  string  $prefix  The document number prefix, stored uppercase.
     * @param  int  $startingNumber  The number to start from on an empty database.
     * @param  string  $table  The table issued numbers are read from.
     * @param  string  $column  The column holding the document number.
     */
    protected function seedDocumentSequence(
        string $type,
        string $prefix,
        int $startingNumber,
        string $table,
        string $column
    ): void {
        $existing = DB::table('document_sequences')
            ->where('type', $type)
            ->first(['id', 'created_at', 'next_number']);

        $nextNumber = max(
            $existing->next_number ?? 0,
            $this->highestIssuedNumber($prefix, $table, $column) + 1,
            $startingNumber
        );

        DB::table('document_sequences')->updateOrInsert(
            ['type' => $type],
            [
                'id' => $existing->id ?? (string) Str::uuid7(),
                'prefix' => $prefix,
                'next_number' => $nextNumber,
                'created_at' => $existing->created_at ?? now(),
                'updated_at' => now(),
            ]
        );
    }

    /**
     * The highest numeric document number already issued for a prefix.
     *
     * Prefixes are stored uppercase while documents are written lowercase, so
     * the match is case-insensitive. A purely numeric tail is required, so a
     * seeded reference such as PO-DEMO-10001 is never read as a sequence number.
     *
     * @param  string  $prefix  The document number prefix.
     * @param  string  $table  The table issued numbers are read from.
     * @param  string  $column  The column holding the document number.
     * @return int The highest issued number, or 0 when none has been issued.
     */
    private function highestIssuedNumber(string $prefix, string $table, string $column): int
    {
        $lowerPrefix = strtolower($prefix);

        $numbers = DB::table($table)
            ->whereRaw("lower({$column}) LIKE ?", [$lowerPrefix.'%'])
            ->pluck($column)
            ->map(static function (string $documentNumber) use ($lowerPrefix): int {
                $digits = substr($documentNumber, strlen($lowerPrefix));

                return preg_match('/^\d+$/', $digits) === 1 ? (int) $digits : 0;
            })
            ->filter(static fn (int $number): bool => $number > 0)
            ->all();

        return $numbers === [] ? 0 : max($numbers);
    }
}
