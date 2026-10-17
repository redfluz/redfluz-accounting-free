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

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use LogicException;

/**
 * Seed money-account master records (banks, cash, e-wallets) linked to chart accounts.
 */
class BankSeeder extends Seeder
{
    /**
     * Seed realistic money-account reference data into three separate tables.
     */
    public function run(): void
    {
        $chartIds = DB::table('charts')
            ->where('isBank', true)
            ->whereNull('deleted_at')
            ->pluck('id', 'code');

        // -- Bank accounts ---------------------------------------------------
        $bankAccounts = [
            ['chart' => '1100', 'name' => 'Malayan Banking Berhad', 'account_name' => 'Redfluz Trading Sdn Bhd', 'account_number' => '514012345678', 'account_type' => 'Current Account', 'branch_name' => 'Kuala Lumpur Main Branch', 'bank_code' => 'MBB', 'swift_code' => 'MBBEMYKL'],
            ['chart' => '1110', 'name' => 'CIMB Bank Berhad', 'account_name' => 'Redfluz Trading Sdn Bhd', 'account_number' => '8001234567', 'account_type' => 'Savings Account', 'branch_name' => 'Kuala Lumpur Main Branch', 'bank_code' => 'CIMB', 'swift_code' => 'CIBBMYKL'],
            ['chart' => '1120', 'name' => 'Public Bank Berhad', 'account_name' => 'Redfluz Trading Sdn Bhd', 'account_number' => '320123456789', 'account_type' => 'Fixed Deposit', 'branch_name' => 'Kuala Lumpur Main Branch', 'bank_code' => 'PBB', 'swift_code' => 'PBBEMYKL'],
        ];

        // -- Cash accounts ---------------------------------------------------
        // Company cash that is not banked: one held on hand, one held in a
        // point of sales drawer. Both are assets, and both need to be money
        // accounts of their own so each can hold a balance and so cash can be
        // banked from one to the other. Neither has an account number, a
        // branch, a bank code or a swift code, because none of those apply.
        // More than one cash account, because a business that counts a
        // drawer at the counter and a drawer in the warehouse cannot tell
        // them apart on one figure. Petty Cash is the default so money
        // leaves from somewhere known when a document does not say.
        $cashAccounts = [
            ['chart' => '1000', 'name' => 'Cash in Hand', 'account_name' => 'Redfluz Trading Sdn Bhd', 'account_type' => 'Cash on Hand'],
            ['chart' => '1130', 'name' => 'Petty Cash', 'account_name' => 'Redfluz Trading Sdn Bhd', 'account_type' => 'Petty Cash', 'is_default' => true],
            ['chart' => '1140', 'name' => 'Counter Drawer 1', 'account_name' => 'Redfluz Trading Sdn Bhd', 'account_type' => 'Cash Drawer', 'is_default' => false],
            ['chart' => '1141', 'name' => 'Warehouse Drawer 2', 'account_name' => 'Redfluz Trading Sdn Bhd', 'account_type' => 'Cash Drawer', 'is_default' => false],
        ];

        // -- E-wallet accounts -----------------------------------------------
        // Wallets are money accounts like any other, but they are issued by
        // somebody rather than opened by the company, so each names the
        // provider that issued it. None of the banking codes apply.
        $eWalletAccounts = [
            ['chart' => '1150', 'name' => 'Touch n Go Wallet', 'account_name' => 'Redfluz Trading Sdn Bhd', 'account_number' => '012-3456789', 'account_type' => 'E-Wallet', 'provider' => 'Touch n Go eWallet', 'is_default' => true],
            ['chart' => '1151', 'name' => 'GrabPay Wallet', 'account_name' => 'Redfluz Trading Sdn Bhd', 'account_number' => '012-9876543', 'account_type' => 'E-Wallet', 'provider' => 'GrabPay', 'is_default' => false],
        ];

        $allAccounts = array_merge($bankAccounts, $cashAccounts, $eWalletAccounts);

        $missingCodes = collect($allAccounts)->pluck('chart')->diff($chartIds->keys());
        if ($missingCodes->isNotEmpty()) {
            throw new LogicException('Missing bank chart accounts: '.$missingCodes->implode(', '));
        }

        $now = now();

        // Providers are managed through /e-wallet-providers, not seeded.
        // Resolve by name if they exist, otherwise leave null so the account
        // can still be created and the provider assigned later.
        $providerIds = DB::table('e_wallet_providers')
            ->whereNull('deleted_at')
            ->pluck('id', 'name');

        // -- Upsert banks ----------------------------------------------------
        $bankRows = collect($bankAccounts)->map(fn (array $account): array => [
            'id' => (string) Str::uuid7(),
            'chart_id' => $chartIds->get($account['chart']),
            'name' => $account['name'],
            'account_name' => $account['account_name'],
            'account_number' => $account['account_number'],
            'account_type' => $account['account_type'],
            'branch_name' => $account['branch_name'],
            'bank_code' => $account['bank_code'],
            'swift_code' => $account['swift_code'],
            'iban' => null,
            'routing_number' => null,
            'country_code' => 'MY',
            'email' => null,
            'phone' => null,
            'website' => null,
            'address' => null,
            'notes' => null,
            'is_active' => true,
            'is_default' => $account['is_default'] ?? false,
            'created_at' => $now,
            'updated_at' => $now,
            'deleted_at' => null,
        ])->all();

        DB::table('banks')->upsert($bankRows, ['chart_id'], [
            'name', 'account_name', 'account_number', 'account_type', 'branch_name',
            'bank_code', 'swift_code', 'country_code', 'is_active',
            'is_default', 'updated_at', 'deleted_at',
        ]);

        // -- Upsert cash accounts --------------------------------------------
        $cashRows = collect($cashAccounts)->map(fn (array $account): array => [
            'id' => (string) Str::uuid7(),
            'chart_id' => $chartIds->get($account['chart']),
            'name' => $account['name'],
            'account_name' => $account['account_name'],
            'account_type' => $account['account_type'],
            'country_code' => 'MY',
            'email' => null,
            'phone' => null,
            'website' => null,
            'address' => null,
            'notes' => null,
            'is_active' => true,
            'is_default' => $account['is_default'] ?? false,
            'asset_location_id' => null,
            'float_amount' => 0,
            'created_at' => $now,
            'updated_at' => $now,
            'deleted_at' => null,
        ])->all();

        DB::table('cash_accounts')->upsert($cashRows, ['chart_id'], [
            'name', 'account_name', 'account_type', 'country_code',
            'is_active', 'is_default', 'asset_location_id', 'float_amount',
            'updated_at', 'deleted_at',
        ]);

        // -- Upsert e-wallet accounts ----------------------------------------
        $eWalletRows = collect($eWalletAccounts)->map(fn (array $account): array => [
            'id' => (string) Str::uuid7(),
            'chart_id' => $chartIds->get($account['chart']),
            'name' => $account['name'],
            'account_name' => $account['account_name'],
            'account_number' => $account['account_number'],
            'account_type' => $account['account_type'],
            // Only a wallet is issued by somebody. Resolved by name so a
            // provider renamed in the seed does not silently attach the wrong
            // issuer to an account.
            'e_wallet_provider_id' => $providerIds->get($account['provider']),
            'country_code' => 'MY',
            'email' => null,
            'phone' => null,
            'website' => null,
            'address' => null,
            'notes' => null,
            'is_active' => true,
            'is_default' => $account['is_default'] ?? false,
            'created_at' => $now,
            'updated_at' => $now,
            'deleted_at' => null,
        ])->all();

        DB::table('e_wallet_accounts')->upsert($eWalletRows, ['chart_id'], [
            'name', 'account_name', 'account_number', 'account_type',
            'e_wallet_provider_id', 'country_code', 'is_active',
            'is_default', 'updated_at', 'deleted_at',
        ]);
    }
}
