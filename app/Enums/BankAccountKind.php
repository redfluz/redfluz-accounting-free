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

namespace App\Enums;

/**
 * What kind of money account a bank record holds.
 *
 * The four accounting principles are the backbone of the chart of accounts, but
 * a company also holds money in places that are not the four principles. Cash in
 * a drawer, money in an e-wallet and a crypto balance are all the same thing for
 * accounting purposes: an account that holds money and can be moved into or out
 * of. Only the medium differs.
 *
 * Recording the medium here rather than inferring it from the name is what lets
 * a cash deposit pick a drawer account as its source and a real bank as its
 * destination. Without it, the choice would be a free text field and a deposit
 * could pair a crypto wallet with a savings account with nothing to say whether
 * that was intended.
 *
 * accounts_type remains on the bank record for the human description of the
 * account, which is unbounded: a current account is a bank account, but "Petty
 * Cash" or "Cash in Hand" are not really account types at all, they are where
 * the cash is kept.
 */
enum BankAccountKind: string
{
    // A real bank account: money at a financial institution.
    case BANK = 'bank';

    // Physical cash held by the business rather than banked.
    case CASH = 'cash';

    // Money held in a third party wallet or payment app.
    //
    // The kind is recorded, but which provider is not: the providers themselves
    // are records rather than a fixed set of values. Touch 'n Go, Boost and
    // GrabPay are common in Malaysia, and every other market has its own set, so
    // naming them here would mean a release to add a wallet another country uses
    // already. A new provider is a new row, not a code change.
    case E_WALLET = 'e_wallet';

    // A crypto balance.
    case CRYPTO = 'crypto';

    /**
     * Whether this kind holds physical cash rather than a balance at a provider.
     */
    public function isPhysicalCash(): bool
    {
        return $this === self::CASH;
    }

    /**
     * Whether an account of this kind can have a minimum balance to maintain.
     *
     * Only cash can. A drawer is float: it is meant to be topped back up to a
     * figure and held there, which is what makes a cash shortfall worth knowing
     * about. A bank account or a wallet is refilled on its own schedule by
     * whoever owns it, and treating a low balance there as an exception would
     * report constantly on ordinary days.
     */
    public function hasFloatTarget(): bool
    {
        return $this === self::CASH;
    }

    /**
     * The name this kind is shown under.
     *
     * The backing value is a storage detail — 'e_wallet' rather than 'e-wallet',
     * because it has to survive a round trip through the database and the route
     * that names the page — and a user reading a page should never be shown it.
     */
    public function label(): string
    {
        return match ($this) {
            self::BANK => __('money_ledgers.kinds.bank'),
            self::CASH => __('money_ledgers.kinds.cash'),
            self::E_WALLET => __('money_ledgers.kinds.e_wallet'),
            self::CRYPTO => __('money_ledgers.kinds.crypto'),
        };
    }

    /**
     * Every value this enum can hold.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
