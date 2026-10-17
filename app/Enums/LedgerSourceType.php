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
 * What a row in a sub ledger was raised by.
 *
 * A sub ledger row is not a document in its own right. It is the receivable or
 * payable side of a document, and the document it came from is named here so a
 * row can be drilled back up to the document that caused it. That is the only
 * reason this enum exists: the ledger stores a figure, this stores the
 * provenance of that figure.
 *
 * The values pair a side with a kind. SALES_INVOICE belongs to the receivables
 * ledger and PURCHASE_INVOICE to the payables one, and neither may appear in the
 * other. A row whose source_type does not belong to the ledger holding it is a
 * bug, which is why the two sides are named apart rather than reusing the
 * document type on both.
 */
enum LedgerSourceType: string
{
    // Receivable sources. Money the customer owes.
    case SALES_INVOICE = 'sales_invoice';
    case SALES_ADJUSTMENT = 'sales_adjustment';
    case RECEIPT = 'receipt';
    case RECEIPT_REFUND = 'receipt_refund';

    // Payable sources. Money owed to the supplier.
    case PURCHASE_INVOICE = 'purchase_invoice';
    case PURCHASE_ADJUSTMENT = 'purchase_adjustment';
    case PAYMENT_VOUCHER = 'payment_voucher';
    case SUPPLIER_REFUND = 'supplier_refund';

    // Money movements. Neither receivable nor payable: a bank journal names no
    // counterparty, so it raises no sub-ledger row on either side.
    case BANK_JOURNAL = 'bank_journal';

    /**
     * Whether this source belongs to the receivables ledger.
     */
    public function isReceivable(): bool
    {
        return in_array($this, [
            self::SALES_INVOICE,
            self::SALES_ADJUSTMENT,
            self::RECEIPT,
            self::RECEIPT_REFUND,
        ], true);
    }

    /**
     * The sources that belong to the receivables ledger.
     *
     * @return array<int, string>
     */
    public static function receivableValues(): array
    {
        return array_map(
            static fn (self $case): string => $case->value,
            array_filter(self::cases(), static fn (self $case): bool => $case->isReceivable()),
        );
    }

    /**
     * The sources that belong to the payables ledger.
     *
     * @return array<int, string>
     */
    public static function payableValues(): array
    {
        return array_values(array_diff(self::values(), self::receivableValues()));
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
