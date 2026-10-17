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

namespace App\Traits;

use App\Enums\LedgerSubType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

trait GeneratesDocumentNumber
{
    /**
     * Generate a sequential document number for the given ledger sub-type.
     *
     * @param  LedgerSubType  $subtype  The ledger sub-type that determines the sequence and prefix.
     * @return string The next document number in the sequence.
     *
     * @throws Throwable
     */
    public function generateDocumentNumber(LedgerSubType $subtype): string
    {
        return $this->generateSequenceNumber(
            $subtype->value,
            $subtype->prefix()
        );
    }

    /**
     * Generate a quotation number from its independent sequence.
     *
     * @throws Throwable
     */
    public function generateQuotationNumber(): string
    {
        return $this->generateSequenceNumber('quotation', 'QT');
    }

    /**
     * Generate a sales order number from its independent sequence.
     *
     * @throws Throwable
     */
    public function generateSalesOrderNumber(): string
    {
        return $this->generateSequenceNumber('sales_order', 'SO');
    }

    /**
     * Generate a delivery order number from its independent sequence.
     *
     * @throws Throwable
     */
    public function generateDeliveryOrderNumber(): string
    {
        return $this->generateSequenceNumber('delivery_order', 'DO');
    }

    /**
     * Generate a purchase order number from its independent sequence.
     *
     * @throws Throwable
     */
    public function generatePurchaseOrderNumber(): string
    {
        return $this->generateSequenceNumber('purchase_order', 'PO');
    }

    /**
     * Generate a goods received note number from its independent sequence.
     *
     * @throws Throwable
     */
    public function generateGoodsReceiptNumber(): string
    {
        return $this->generateSequenceNumber('goods_receipt', 'GRN');
    }

    /**
     * Generate a stock transfer number from its independent sequence.
     *
     * @throws Throwable
     */
    public function generateStockTransferNumber(): string
    {
        return $this->generateSequenceNumber('stock_transfer', 'ST');
    }

    /**
     * Generate a stock take number from its independent sequence.
     *
     * @throws Throwable
     */
    public function generateStockTakeNumber(): string
    {
        return $this->generateSequenceNumber('stock_take', 'STK');
    }

    /**
     * Generate a purchase-return number independently from goods receipts.
     *
     * @return string The next return number.
     *
     * @throws Throwable
     */
    public function generatePurchaseReturnNumber(): string
    {
        return $this->generateSequenceNumber('purchase_return', 'PRTN');
    }

    /**
     * Generate an RMA number from its independent sequence.
     *
     * @throws Throwable
     */
    public function generateRmaNumber(): string
    {
        return $this->generateSequenceNumber('rma', 'RMA');
    }

    /**
     * Generate the next locked number for one document sequence.
     *
     * @param  string  $type  The document sequence type key.
     * @param  string  $defaultPrefix  The prefix used when no sequence row exists yet.
     * @return string The formatted document number (prefix + sequential number).
     *
     * @throws Throwable
     */
    private function generateSequenceNumber(string $type, string $defaultPrefix): string
    {
        return DB::transaction(function () use ($type, $defaultPrefix): string {
            $sequence = DB::table('document_sequences')
                ->where('type', $type)
                ->lockForUpdate()
                ->first();

            if ($sequence === null) {
                $nextNumber = 10001;
                $prefix = $defaultPrefix;

                DB::table('document_sequences')->insert([
                    'id' => (string) Str::uuid7(),
                    'type' => $type,
                    'prefix' => $prefix,
                    'next_number' => $nextNumber + 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                $rawNextNumber = $sequence->next_number;
                $nextNumber = is_numeric($rawNextNumber) ? (int) $rawNextNumber : 10001;
                $rawPrefix = $sequence->prefix;
                $prefix = is_string($rawPrefix) ? $rawPrefix : $defaultPrefix;

                DB::table('document_sequences')
                    ->where('id', $sequence->id)
                    ->update([
                        'next_number' => $nextNumber + 1,
                        'updated_at' => now(),
                    ]);
            }

            return mb_strtolower($prefix).$nextNumber;
        });
    }
}
