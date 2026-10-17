<?php

/**
 * ============================================================================
 *
 * @author      redfluz
 *
 * @github      https://github.com/redfluz
 *
 * @youtube     https://www.youtube.com/@redfluz
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

declare(strict_types=1);

namespace App\Support\Posting;

use App\Services\DecimalService;
use App\Support\PostingException;

/**
 * One side of a double entry, as the poster receives it.
 *
 * This is the input the whole posting layer is built on. Every document that
 * posts — a receipt, a payment voucher, a refund, an invoice — reduces its lines
 * to a list of these, and everything the poster writes to any ledger is derived
 * from them. There is no array shape beside it to fall back on: a leg carries a
 * chart account id and one amount, and the types say so.
 *
 * A leg is deliberately single-sided. A document line that debits one account
 * and credits another is two movements, and handing the poster a row that holds
 * both is how a ledger ends up with two movements pretending to be one. The
 * callers that have double-sided rows — the client ledger entries an invoice is
 * built from — split them first, one leg per side.
 *
 * The amounts are numeric strings, not floats. That is the point of the object:
 * money that has been through a float has already been rounded by the time the
 * ledger sees it, and a ledger is the one place that must never round.
 *
 * The constructor is private because a leg has to say which side it is. Build
 * one with {@see PostingLeg::debit()} or {@see PostingLeg::credit()}, or split a
 * client ledger entry with {@see PostingLeg::fromEntry()}.
 */
final class PostingLeg
{
    /**
     * @param  string|null  $id  The client ledger row this movement came from, when
     *                           it came from one. Becomes the general ledger row's
     *                           own id, which is how a second posting of the same
     *                           document is caught before it writes.
     * @param  string  $chartId  The chart account the movement is against.
     * @param  bool  $isDebit  Which side of the entry this leg carries.
     * @param  numeric-string  $debit  The debit amount, "0.00" when this is a credit leg.
     * @param  numeric-string  $credit  The credit amount, "0.00" when this is a debit leg.
     * @param  string|null  $description  What the movement was for.
     * @param  string|null  $referenceNumber  What the movement settles or corrects.
     */
    private function __construct(
        public readonly ?string $id,
        public readonly string $chartId,
        public readonly bool $isDebit,
        public readonly string $debit,
        public readonly string $credit,
        public readonly ?string $description,
        public readonly ?string $referenceNumber,
    ) {}

    /**
     * A movement against the debit side of an account.
     *
     * @param  string  $chartId  The chart account the movement is against.
     * @param  numeric-string  $amount  The amount, normalised to an exact decimal.
     * @param  string|null  $description  What the movement was for.
     * @param  string|null  $referenceNumber  What the movement settles or corrects.
     * @param  string|null  $id  The client ledger row this movement came from, if any.
     */
    public static function debit(
        string $chartId,
        string $amount,
        ?string $description = null,
        ?string $referenceNumber = null,
        ?string $id = null,
    ): self {
        return self::movement($id, $chartId, true, $amount, '0.00', $description, $referenceNumber);
    }

    /**
     * A movement against the credit side of an account.
     *
     * @param  string  $chartId  The chart account the movement is against.
     * @param  numeric-string  $amount  The amount, normalised to an exact decimal.
     * @param  string|null  $description  What the movement was for.
     * @param  string|null  $referenceNumber  What the movement settles or corrects.
     * @param  string|null  $id  The client ledger row this movement came from, if any.
     */
    public static function credit(
        string $chartId,
        string $amount,
        ?string $description = null,
        ?string $referenceNumber = null,
        ?string $id = null,
    ): self {
        return self::movement($id, $chartId, false, '0.00', $amount, $description, $referenceNumber);
    }

    /**
     * The side of the entry this leg carries an amount on, as a movement.
     *
     * Used to derive routing legs from rows that were built for a wider purpose,
     * such as a full general ledger row that also carries the document line.
     * Both sides set means the row is two movements, which a leg cannot hold.
     *
     * @param  string|null  $id  The client ledger row the movement came from, if any.
     * @param  string  $chartId  The chart account the movement is against.
     * @param  bool  $isDebit  Which side of the entry carries the amount.
     * @param  numeric-string  $debit  The debit amount.
     * @param  numeric-string  $credit  The credit amount.
     * @param  string|null  $description  What the movement was for.
     * @param  string|null  $referenceNumber  What the movement settles or corrects.
     */
    public static function movement(
        ?string $id,
        string $chartId,
        bool $isDebit,
        string $debit,
        string $credit,
        ?string $description = null,
        ?string $referenceNumber = null,
    ): self {
        $debit = DecimalService::normalize($debit);
        $credit = DecimalService::normalize($credit);

        if (bccomp($debit, '0.00', 2) !== 0 && bccomp($credit, '0.00', 2) !== 0) {
            throw PostingException::oneSidedEntryRequired($id ?? $chartId);
        }

        return new self(
            id: $id,
            chartId: $chartId,
            isDebit: $isDebit,
            debit: $debit,
            credit: $credit,
            description: $description,
            referenceNumber: $referenceNumber,
        );
    }

    /**
     * The amount on the side this leg carries.
     *
     * @return numeric-string The debit amount on a debit leg, the credit amount otherwise.
     */
    public function amount(): string
    {
        return $this->isDebit ? $this->debit : $this->credit;
    }

    /**
     * Sum the debit side of a double entry.
     *
     * @param  list<self>  $legs  The double entry.
     * @return numeric-string The total, to two decimal places.
     */
    public static function sumDebit(array $legs): string
    {
        return self::sum($legs, true);
    }

    /**
     * Sum the credit side of a double entry.
     *
     * @param  list<self>  $legs  The double entry.
     * @return numeric-string The total, to two decimal places.
     */
    public static function sumCredit(array $legs): string
    {
        return self::sum($legs, false);
    }

    /**
     * Sum one side of a double entry in bcmath, so the total is exact.
     *
     * @param  list<self>  $legs  The double entry.
     * @param  bool  $debit  True to sum the debit side, false for the credit side.
     * @return numeric-string The total, to two decimal places.
     */
    private static function sum(array $legs, bool $debit): string
    {
        $total = '0.00';

        foreach ($legs as $leg) {
            $total = bcadd($total, $debit ? $leg->debit : $leg->credit, 2);
        }

        return $total;
    }
}
