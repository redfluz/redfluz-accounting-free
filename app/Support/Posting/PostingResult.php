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

/**
 * What one posting wrote, counted per ledger.
 *
 * The poster writes to up to five tables in one call — the general ledger, one
 * or more of the three money ledgers, and the receivable or payable ledger — and
 * a caller that wants to know what happened should not have to infer it from the
 * shape of its own legs. This says it outright: how many rows landed in each.
 *
 * It replaced the bare array the poster used to return because a string-keyed
 * array of counts is a contract nobody can enforce. A caller reading
 * `$written['ar']` could misspell the key and get a silent null; a caller
 * reading `$result->ar` gets an integer or the code does not run.
 */
final class PostingResult
{
    /**
     * @param  int  $general  Rows written to the general ledger.
     * @param  int  $cash  Rows written to the cash ledger.
     * @param  int  $bank  Rows written to the bank ledger.
     * @param  int  $eWallet  Rows written to the e-wallet ledger.
     * @param  int  $ar  Rows written to the receivable ledger.
     * @param  int  $ap  Rows written to the payable ledger.
     */
    public function __construct(
        public readonly int $general = 0,
        public readonly int $cash = 0,
        public readonly int $bank = 0,
        public readonly int $eWallet = 0,
        public readonly int $ar = 0,
        public readonly int $ap = 0,
    ) {}

    /**
     * Every row the posting wrote, across all the ledgers it belongs in.
     */
    public function total(): int
    {
        return $this->general + $this->cash + $this->bank + $this->eWallet + $this->ar + $this->ap;
    }
}
