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

use App\Enums\StockLocationType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SystemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if --demo option exists and is set
        $demo = false;
        if ($this->command && $this->command->getDefinition()->hasOption('demo')) {
            $demo = (bool) $this->command->option('demo');
        }

        // 1. Seed Roles with Dashboards
        $roles = [
            ['name' => 'Super Admin', 'dashboard' => 'user', 'is_default' => false],
            ['name' => 'Manager',     'dashboard' => 'user', 'is_default' => false],
            ['name' => 'Editor',      'dashboard' => 'user', 'is_default' => false],
            ['name' => 'Finance',     'dashboard' => 'user', 'is_default' => false],
            ['name' => 'User',        'dashboard' => 'user', 'is_default' => true],
            ['name' => 'Human',       'dashboard' => 'user', 'is_default' => false],
            ['name' => 'Sales',       'dashboard' => 'user', 'is_default' => false],
            ['name' => 'Technician',  'dashboard' => 'user', 'is_default' => false],
            ['name' => 'Clerk',       'dashboard' => 'user', 'is_default' => false],
        ];

        if ($demo) {
            $roles = array_merge($roles, [
                ['name' => 'Test Finance', 'dashboard' => 'user', 'is_default' => false],
                ['name' => 'Test AP',      'dashboard' => 'user', 'is_default' => false],
                ['name' => 'Test AR',      'dashboard' => 'user', 'is_default' => false],
                ['name' => 'Test HR',      'dashboard' => 'user', 'is_default' => false],
            ]);
        }

        foreach ($roles as $role) {
            $existing = DB::table('roles')->where('name', $role['name'])->first();

            DB::table('roles')->updateOrInsert(
                ['name' => $role['name']],
                [
                    'id' => $existing->id ?? (string) Str::uuid7(),
                    'dashboard' => $role['dashboard'],
                    'is_default' => $role['is_default'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        // Fetch UUID Keys
        $adminRoleId = DB::table('roles')->where('name', 'Super Admin')->value('id');
        $managerRoleId = DB::table('roles')->where('name', 'Manager')->value('id');
        $editorRoleId = DB::table('roles')->where('name', 'Editor')->value('id');
        $userRoleId = DB::table('roles')->where('name', 'User')->value('id');
        $financeRoleId = DB::table('roles')->where('name', 'Finance')->value('id');
        $humanRoleId = DB::table('roles')->where('name', 'Human')->value('id');
        $salesRoleId = DB::table('roles')->where('name', 'Sales')->value('id');
        $technicianRoleId = DB::table('roles')->where('name', 'Technician')->value('id');
        $clerkRoleId = DB::table('roles')->where('name', 'Clerk')->value('id');

        // 2. System Core Users (Using DB query builder to bypass Eloquent events)
        $users = [
            ['name' => 'Super Admin', 'email' => 'admin@example.com', 'role_id' => $adminRoleId],
            ['name' => 'Manager User', 'email' => 'manager@example.com', 'role_id' => $managerRoleId],
            ['name' => 'Editor User', 'email' => 'editor@example.com', 'role_id' => $editorRoleId],
            ['name' => 'Finance User', 'email' => 'finance@example.com', 'role_id' => $financeRoleId],
            ['name' => 'Human Resources User', 'email' => 'hr@example.com', 'role_id' => $humanRoleId],
            ['name' => 'Sales User', 'email' => 'sales@example.com', 'role_id' => $salesRoleId],
            ['name' => 'Standard User', 'email' => 'user@example.com', 'role_id' => $userRoleId],
            ['name' => 'Operations Clerk', 'email' => 'clerk@example.com', 'role_id' => $clerkRoleId],
            ['name' => 'Technical Services Manager', 'email' => 'technical.manager@example.com', 'role_id' => $managerRoleId],
            ['name' => 'Workshop Technician One', 'email' => 'technician.one@example.com', 'role_id' => $technicianRoleId],
            ['name' => 'Workshop Technician Two', 'email' => 'technician.two@example.com', 'role_id' => $technicianRoleId],
            ['name' => 'Field Technician', 'email' => 'technician.field@example.com', 'role_id' => $technicianRoleId],
        ];

        if ($demo) {
            $testFinanceRoleId = DB::table('roles')->where('name', 'Test Finance')->value('id');
            $testApRoleId = DB::table('roles')->where('name', 'Test AP')->value('id');
            $testArRoleId = DB::table('roles')->where('name', 'Test AR')->value('id');
            $testHrRoleId = DB::table('roles')->where('name', 'Test HR')->value('id');

            $users = array_merge($users, [
                ['name' => 'Finance Test User', 'email' => 'finance.test@example.com', 'role_id' => $testFinanceRoleId],
                ['name' => 'AP Test User', 'email' => 'ap.test@example.com', 'role_id' => $testApRoleId],
                ['name' => 'AR Test User', 'email' => 'ar.test@example.com', 'role_id' => $testArRoleId],
                ['name' => 'HR Test User', 'email' => 'hr.test@example.com', 'role_id' => $testHrRoleId],
                ['name' => 'Payroll Age 60 Test User', 'email' => 'payroll.age60@example.com', 'role_id' => $userRoleId],
                ['name' => 'Payroll Age 75 Test User', 'email' => 'payroll.age75@example.com', 'role_id' => $userRoleId],
                ['name' => 'Payroll Non Malaysian Test User', 'email' => 'payroll.foreign@example.com', 'role_id' => $userRoleId],
            ]);
        }

        foreach ($users as $u) {
            $existingId = DB::table('users')->where('email', $u['email'])->value('id');

            DB::table('users')->updateOrInsert(
                ['email' => $u['email']],
                [
                    'id' => $existingId ?? (string) Str::uuid7(),
                    'name' => $u['name'],
                    'password' => Hash::make('password'),
                    'role_id' => $u['role_id'],
                    'email_verified_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        // 4. System Configuration Settings
        $myrCurrencyId = DB::table('currencies')->where('code', 'MYR')->value('id');

        DB::table('settings')->updateOrInsert(
            ['company_email' => 'support@tada.com'],
            [
                'id' => DB::table('settings')->where('company_email', 'support@tada.com')->value('id') ?? (string) Str::uuid7(),
                'currency_id' => $myrCurrencyId,
                'company_name' => 'REDFLUZ BERHAD',
                'company_registration_no' => '202001234567',
                'company_tax_no' => 'C1234567890',
                'company_phone' => '+603-8888-9999',
                'company_mobile' => '+6012-345-6789',
                'company_postcode' => '63000',
                'company_city' => 'Cyberjaya',
                'company_state' => 'Selangor',
                'company_country' => 'Malaysia',
                'logo_path' => null,
                'short_date_format' => 'd/m/Y',
                'long_date_format' => 'd/m/Y H:i:s',
                'address' => "Level 20, TADA Tower\nPersiaran Multimedia\nCyberjaya, Selangor",
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // 5. Default stock location — required for GRN / DO flows even on
        //    clean installs (no UI to create one manually in the free version).
        DB::table('stock_locations')->updateOrInsert(
            ['code' => 'WH-001'],
            [
                'id' => DB::table('stock_locations')->where('code', 'WH-001')->value('id') ?? (string) Str::uuid7(),
                'name' => 'Default Warehouse',
                'type' => StockLocationType::WAREHOUSE->value,
                'address' => null,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null,
            ]
        );
    }
}
