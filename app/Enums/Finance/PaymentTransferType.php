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

namespace app\Enums\Finance;

use App\Enums\Bank\BankAccountKind;

enum PaymentTransferType: string
{
    case CHEQUE = 'cheque';
    case BANK_TRANSFER = 'bank_transfer';
    case E_WALLET = 'e_wallet';
    case CASH = 'cash';

    public function label(): string
    {
        return match ($this) {
            self::CHEQUE => __('payments.transfer_types.cheque'),
            self::BANK_TRANSFER => __('payments.transfer_types.bank_transfer'),
            self::E_WALLET => __('payments.transfer_types.e_wallet'),
            self::CASH => __('payments.transfer_types.cash'),
        };
    }

    public function accountKind(): BankAccountKind
    {
        return match ($this) {
            self::CHEQUE, self::BANK_TRANSFER => BankAccountKind::BANK,
            self::E_WALLET => BankAccountKind::E_WALLET,
            self::CASH => BankAccountKind::CASH,
        };
    }

    public function accountColumn(): string
    {
        return match ($this->accountKind()) {
            BankAccountKind::BANK => 'bank_account_id',
            BankAccountKind::CASH => 'cash_account_id',
            BankAccountKind::E_WALLET => 'e_wallet_account_id',
            BankAccountKind::CRYPTO => 'bank_account_id',
        };
    }

    /** @return list<self> */
    public static function refundable(): array
    {
        // Cash is left out on purpose. Money handed back has to leave an account
        // the refund can name, and a till cannot be named the way a bank or a
        // wallet can: notes passed over a counter leave no account for the
        // movement to post against.
        return [self::CHEQUE, self::BANK_TRANSFER, self::E_WALLET];
    }

    /** @return list<string> */
    public static function refundableValues(): array
    {
        return array_column(self::refundable(), 'value');
    }

    public static function isRefundable(?self $transferType): bool
    {
        return $transferType !== null && in_array($transferType, self::refundable(), true);
    }
}
