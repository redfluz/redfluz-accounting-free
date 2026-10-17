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
use App\Enums\FileResources;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        // -----------------------------------------------------------------
        // SUPPLIER OUTSTANDING – add page to the PURCHASES folder
        // -----------------------------------------------------------------
        $purchasesFolderId = DB::table('folders')->where('name', 'PURCHASES')->value('id');
        $purchaseInvoicesPageId = DB::table('pages')->where('route_name', FileResources::PURCHASE_INVOICES->value)->value('id');

        if (is_string($purchasesFolderId) && is_string($purchaseInvoicesPageId)) {
            $existing = DB::table('pages')->where('route_name', FileResources::SUPPLIER_OUTSTANDING->value)->first();
            $supplierPageId = $existing->id ?? (string) Str::uuid7();
            DB::table('pages')->updateOrInsert(['id' => $supplierPageId], [
                'folder_id' => $purchasesFolderId,
                'name' => 'SUPPLIER OUTSTANDING',
                'route_name' => FileResources::SUPPLIER_OUTSTANDING->value,
                'icon' => 'wallet-cards',
                'deleted_at' => null,
                'created_at' => $existing->created_at ?? now(),
                'updated_at' => now(),
            ]);

            foreach (DB::table('page_permissions')->where('page_id', $purchaseInvoicesPageId)->whereNull('deleted_at')->get() as $permission) {
                $existingPermission = DB::table('page_permissions')
                    ->where('page_id', $supplierPageId)
                    ->where('role_id', $permission->role_id)
                    ->first();
                DB::table('page_permissions')->updateOrInsert(['id' => $existingPermission->id ?? (string) Str::uuid7()], [
                    'role_id' => $permission->role_id,
                    'page_id' => $supplierPageId,
                    'can_read' => $permission->can_read,
                    'can_add' => false,
                    'can_update' => false,
                    'can_remove' => false,
                    'can_approve' => false,
                    'can_cancel' => false,
                    'deleted_at' => null,
                    'created_at' => $existingPermission->created_at ?? now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        $routeNames = [
            FileResources::SUPPLIER_OUTSTANDING->value,
        ];

        foreach ($routeNames as $routeName) {
            $page = DB::table('pages')->where('route_name', $routeName)->first();
            if ($page) {
                DB::table('page_permissions')->where('page_id', $page->id)->delete();
                DB::table('pages')->where('id', $page->id)->delete();
            }
        }
    }
};
