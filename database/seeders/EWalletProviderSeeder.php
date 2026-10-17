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

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Seed the e-wallet issuers an account can be attributed to.
 *
 * The table and the foreign key pointing at it both existed before anything wrote
 * here, so an e-wallet account had nowhere to say who issued it. Seeded rather
 * than left empty because the wallet ledger is only identifiable when the
 * issuer is on the account: two balances under one provider's name mean
 * something quite different from two balances under two providers'.
 *
 * identifier_label is what the issuer calls the account on its own statement —
 * a TnG wallet shows a registered number, GrabPay shows a phone number — so the
 * field is named for the provider rather than fixed as an "account number".
 */
class EWalletProviderSeeder extends Seeder
{
    /**
     * Seed the wallet issuers.
     */
    public function run(): void
    {
        $now = Carbon::now();

        $providers = [
            ['name' => 'Touch n Go eWallet', 'country' => 'MY', 'identifier_label' => 'Registered Number', 'is_active' => true],
            ['name' => 'GrabPay', 'country' => 'MY', 'identifier_label' => 'Mobile Number', 'is_active' => true],
            ['name' => 'Boost eWallet', 'country' => 'MY', 'identifier_label' => 'Registered Number', 'is_active' => true],
            ['name' => 'DuitNow', 'country' => 'MY', 'identifier_label' => 'Registered Number', 'is_active' => true],
        ];

        $rows = array_map(static fn (array $provider): array => [
            'id' => (string) Str::uuid7(),
            'name' => $provider['name'],
            'country' => $provider['country'],
            'identifier_label' => $provider['identifier_label'],
            'is_active' => $provider['is_active'],
            'created_at' => $now,
            'updated_at' => $now,
            'deleted_at' => null,
        ], $providers);

        // Keyed on name so re-seeding refreshes the labels rather than adding a
        // second copy of the same issuer beside the first.
        DB::table('e_wallet_providers')->upsert(
            $rows,
            ['name'],
            ['country', 'identifier_label', 'is_active', 'updated_at', 'deleted_at'],
        );
    }
}
