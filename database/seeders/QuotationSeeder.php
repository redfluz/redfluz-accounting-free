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

/**
 * @noinspection ALL
 *
 * @phpstan-ignore-file
 */

namespace Database\Seeders;

use App\Enums\QuotationStatus;
use App\Models\Crm\Company;
use App\Models\Inventory\Product;
use App\Models\Sales\Quotation;
use Database\Seeders\Concerns\SeedsDocumentSequences;
use Illuminate\Database\Seeder;

/**
 * Seeds sales quotations across every status the quotations grid can show.
 *
 * Nothing seeded the quotations table, so the quotations grid was empty
 * on a clean database. A quotation carries no stock movement and posts nothing to
 * the general ledger until it is converted, so seeding it is safe: it cannot
 * disturb inventory or the trial balance.
 *
 * A quotation is one header row in quotations and one line per offered item in
 * quotation_lines, exactly as QuotationController::store() writes it. Each line
 * carries both sides of the posting it would make when converted: the
 * receivable account it debits and the income account it credits.
 *
 * Documents are seeded in ascending number order. Re-running is safe: each
 * quotation is found by its own notes before a new id is minted, so a repeat
 * seed updates in place instead of duplicating. Nothing is ever hard deleted.
 */
class QuotationSeeder extends Seeder
{
    use SeedsDocumentSequences;

    /**
     * The quotations to seed, in ascending document order.
     *
     * The statuses chosen are the ones a quotation actually passes through, so
     * the grid can be exercised end to end: a draft being edited, one sent and
     * awaiting the customer, one accepted, one rejected, and one that was
     * converted into a sales order.
     *
     * @var list<array<string, mixed>>
     */
    private const QUOTATIONS = [
        [
            'number' => 'qt10001',
            'company' => 'Tradenet Financial Terminals',
            'product_sku' => 'PART-LCD-156-FHD',
            'quantity' => '10',
            'unit_price' => '320.00',
            'date' => '2026-09-02',
            'expiry_date' => '2026-10-02',
            'status' => QuotationStatus::DRAFT,
            'is_converted' => false,
            'converted_at' => null,
            'notes' => 'Draft awaiting internal pricing sign-off.',
        ],
        [
            'number' => 'qt10002',
            'company' => 'Horizon Wealth Management',
            'product_sku' => 'PART-LCD-156-FHD',
            'quantity' => '25',
            'unit_price' => '310.00',
            'date' => '2026-09-05',
            'expiry_date' => '2026-10-05',
            'status' => QuotationStatus::SENT,
            'is_converted' => false,
            'converted_at' => null,
            'notes' => 'Sent to the customer, awaiting a decision.',
        ],
        [
            'number' => 'qt10003',
            'company' => 'Apex Capital Advisory Ltd',
            'product_sku' => 'ASU-VIVO-15',
            'quantity' => '5',
            'unit_price' => '2599.00',
            'date' => '2026-09-08',
            'expiry_date' => '2026-10-08',
            'status' => QuotationStatus::ACCEPTED,
            'is_converted' => false,
            'converted_at' => null,
            'notes' => 'Accepted by the customer, not yet converted.',
        ],
        [
            'number' => 'qt10004',
            'company' => 'Khoo & Sons Private Investment',
            'product_sku' => 'ACE-ASP-5',
            'quantity' => '3',
            'unit_price' => '2699.00',
            'date' => '2026-09-10',
            'expiry_date' => '2026-10-10',
            'status' => QuotationStatus::REJECTED,
            'is_converted' => false,
            'converted_at' => null,
            'notes' => 'Rejected: the customer sourced the laptops elsewhere.',
        ],
        [
            'number' => 'qt10005',
            'company' => 'Tradenet Financial Terminals',
            'product_sku' => 'ASU-VIVO-15',
            'quantity' => '8',
            'unit_price' => '2575.00',
            'date' => '2026-09-12',
            'expiry_date' => '2026-10-12',
            'status' => QuotationStatus::CONVERTED,
            'is_converted' => true,
            'converted_at' => '2026-09-14 10:05:00',
            'notes' => 'Converted into sales order so10001.',
        ],
    ];

    /**
     * Seed the quotations.
     */
    public function run(): void
    {
        $this->seedSequence();

        foreach (self::QUOTATIONS as $quotation) {
            $company = Company::query()
                ->where('name', $quotation['company'])
                ->where('is_debtor', true)
                ->first();

            $product = Product::query()->where('sku', $quotation['product_sku'])->first();

            if ($company === null || $product === null) {
                continue;
            }

            $this->seedQuotation($quotation, $company, $product);
        }

        // Re-assert now that the documents exist, so the stored counter is above
        // every number issued even on the very first run. Seeding the sequence
        // only up front would leave it at its starting value and the next
        // quotation created through the UI would collide with these.
        $this->seedSequence();
    }

    /**
     * Seed one quotation as its header and its line.
     *
     * @param  array<string, mixed>  $quotation  The quotation definition.
     * @param  Company  $company  The debtor company.
     * @param  Product  $product  The quoted product.
     */
    private function seedQuotation(
        array $quotation,
        Company $company,
        Product $product
    ): void {
        $amount = bcmul((string) $quotation['quantity'], (string) $quotation['unit_price'], 2);
        $now = now();

        // A quotation is one document with a number, a customer and a state. The
        // header is written once, not repeated per row, so the grid counts
        // quotations rather than legs.
        $existing = Quotation::query()
            ->where('document_number', $quotation['number'])
            ->first(['id', 'created_at']);

        $document = Quotation::query()->updateOrCreate(
            ['document_number' => $quotation['number']],
            [
                'company_id' => $company->id,
                'company_name' => $company->name,
                'contact_id' => null,
                'reference_number' => null,
                'date' => $quotation['date'],
                'expiry_date' => $quotation['expiry_date'],
                'attachment_path' => null,
                'status' => $quotation['status']->value,
                'is_converted' => $quotation['is_converted'],
                'converted_at' => $quotation['converted_at'],
                'notes' => $quotation['notes'],
                'created_at' => $existing->created_at ?? $now,
                'updated_at' => $now,
            ]
        );

        $document->lines()->delete();

        $document->lines()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'quantity' => (string) $quotation['quantity'],
            'unit_price' => (string) $quotation['unit_price'],
            'total_amount' => $amount,
            'description' => $product->name,
        ]);
    }

    /**
     * Seed the quotation number sequence without rewinding it.
     */
    private function seedSequence(): void
    {
        $this->seedDocumentSequence('quotation', 'QT', 10001, 'quotations', 'document_number');
    }
}
