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

use App\Enums\Finance\LedgerStatus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PurchaseAdjustmentSeeder extends Seeder
{
    /**
     * The adjustment scenarios to seed.
     *
     * @var list<array<string, mixed>>
     */
    private const SCENARIOS = [
        [
            'document_number' => 'PDN-DEMO-0001',
            'subtype' => 'purchase_debit_note',
            'reference_number' => 'PI-DEMO-0001',
            'company' => 'Ingram Micro Malaysia Sdn Bhd',
            'date' => '2026-09-15',
            'status' => LedgerStatus::PENDING,
            'total_amount' => '150.00',
            'notes' => 'Debit note for damaged goods returned to supplier.',
            'approved_at' => null,
            'cancelled_at' => null,
        ],
        [
            'document_number' => 'PCN-DEMO-0001',
            'subtype' => 'purchase_credit_note',
            'reference_number' => 'PI-DEMO-0001',
            'company' => 'Ingram Micro Malaysia Sdn Bhd',
            'date' => '2026-09-20',
            'status' => LedgerStatus::PENDING,
            'total_amount' => '400.00',
            'notes' => 'Credit note for overcharged items on invoice.',
            'approved_at' => null,
            'cancelled_at' => null,
        ],
        [
            'document_number' => 'PDN-DEMO-0002',
            'subtype' => 'purchase_debit_note',
            'reference_number' => 'PI-DEMO-0001',
            'company' => 'Ingram Micro Malaysia Sdn Bhd',
            'date' => '2026-10-01',
            'status' => LedgerStatus::CANCELLED,
            'total_amount' => '300.00',
            'notes' => 'Cancelled debit note — duplicate entry.',
            'approved_at' => null,
            'cancelled_at' => '2026-10-02 10:00:00',
        ],
    ];

    /**
     * Seed the purchase adjustments.
     */
    public function run(): void
    {
        foreach (self::SCENARIOS as $scenario) {
            $company = DB::table('companies')
                ->where('name', $scenario['company'])
                ->first(['id', 'name']);

            if ($company === null) {
                continue;
            }

            $existing = DB::table('purchase_adjustments')
                ->where('document_number', $scenario['document_number'])
                ->first(['id', 'created_at']);

            DB::table('purchase_adjustments')->updateOrInsert(
                ['document_number' => $scenario['document_number']],
                [
                    'id' => $existing->id ?? (string) Str::uuid7(),
                    'company_id' => $company->id,
                    'contact_id' => null,
                    'subtype' => $scenario['subtype'],
                    'status' => $scenario['status']->value,
                    'document_number' => $scenario['document_number'],
                    'reference_number' => $scenario['reference_number'],
                    'company_name' => $company->name,
                    'contact_name' => null,
                    'date' => $scenario['date'],
                    'attachment_path' => null,
                    'subtotal' => $scenario['total_amount'],
                    'total_amount' => $scenario['total_amount'],
                    'notes' => $scenario['notes'],
                    'created_by' => null,
                    'created_name' => 'System',
                    'created_at' => $existing->created_at ?? now(),
                    'updated_by' => null,
                    'updated_name' => 'System',
                    'updated_at' => now(),
                    'deleted_by' => null,
                    'deleted_name' => null,
                    'deleted_at' => null,
                    'approved_by' => null,
                    'approved_name' => null,
                    'approved_at' => $scenario['approved_at'],
                    'cancelled_by' => null,
                    'cancelled_name' => null,
                    'cancelled_at' => $scenario['cancelled_at'],
                    'undone_by' => null,
                    'undone_name' => null,
                    'undone_at' => null,
                ]
            );
        }
    }
}
