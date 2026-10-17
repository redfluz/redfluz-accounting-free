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

namespace App\Services;

use App\Models\System\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use stdClass;

class MenuServices
{
    /**
     * Preserve the main menu order defined by the application workflow.
     *
     * @var array<string, int>
     */
    private const array FOLDER_ORDER = [
        'STAFF' => 10,
        'MANAGEMENT' => 20,
        'LEAVE' => 40,
        'CLAIM' => 50,
        'SALARY' => 60,
        'CRM' => 70,
        'INVENTORY' => 80,
        'INVESTMENT' => 90,
        'YEAR' => 100,
        'SALES' => 110,
        'DESIGN' => 120,
        'PURCHASES' => 130,
        'BANK' => 140,
        'FIXED ASSETS' => 150,
        'FINANCE' => 160,
        'CHARTS' => 170,
        'NEWS' => 180,
        'MENU' => 190,
        'ONBOARDING' => 195,
        'SYSTEM' => 200,
    ];

    /**
     * Preserve the business workflow order for operational menu pages.
     *
     * @var array<string, array<string, int|float>>
     */
    private const array PAGE_ORDER = [
        'STAFF' => [
            'DASHBOARD' => 10,
            'LEAVES' => 60,
            'HOLIDAYS' => 70,
            'CLAIM' => 80,
            'SALARY' => 90,
        ],
        'MANAGEMENT' => [
            'DASHBOARD' => 10,
            'EMPLOYEE' => 20,
            'DEPARTMENT' => 30,
            'ORGANISATION' => 40,
        ],
        'SALES' => [
            'PRODUCT PACKAGES' => 10,
            'QUOTATIONS' => 20,
            'SALES ORDERS' => 30,
            'DELIVERY ORDERS' => 40,
            'SALES INVOICES' => 50,
            'RETURN MERCHANDISE AUTHORISATION' => 60,
            'ADJUSTMENT' => 70,
            'SALES ADJUSTMENTS' => 80,
            'CANCELLED SALES ADJUSTMENTS' => 80.1,
            'SALES ADJUSTMENTS HISTORY' => 80.2,
        ],
        // Without an entry here every Purchases page sorts to 999 and keeps
        // whatever order the database returns, so a grouping's live / cancelled
        // / history pages can come out shuffled.
        'PURCHASES' => [
            'PURCHASE ORDER' => 10,
            'PURCHASE ORDER SETTINGS' => 20,
            'GOODS RECEIVED NOTE' => 30,
            'GOODS RECEIPT ADJUSTMENTS' => 40,
            'CANCELLED GOODS RECEIPT ADJUSTMENTS' => 40.1,
            'GOODS RECEIPT ADJUSTMENT HISTORY' => 40.2,
            'PURCHASE INVOICES' => 50,
            'PURCHASE RETURNS' => 60,
            'PURCHASE ADJUSTMENTS' => 70,
            'CANCELLED PURCHASE ADJUSTMENTS' => 70.1,
            'PURCHASE ADJUSTMENTS HISTORY' => 70.2,
        ],
        'BANK' => [
            'DASHBOARD' => 10,
            'BANK ACCOUNTS' => 20,
            'RECEIPTS' => 30,
            'CUSTOMER REFUNDS' => 40,
            'PAYMENT VOUCHERS' => 50,
            'SUPPLIER REFUNDS' => 60,
            'BANK RECONCILIATION' => 70,
        ],
        'LEAVE' => [
            'DASHBOARD' => 10,
            'LEAVE SETTINGS' => 20,
            'LEAVE' => 30,
            'HOLIDAYS' => 40,
        ],
        'CLAIM' => [
            'DASHBOARD' => 10,
            'SETTING' => 20,
            'CLAIM' => 30,
            'BATCH' => 40,
        ],
        'INVENTORY' => [
            'DASHBOARD' => 10,
            'PRODUCTS' => 20,
            'PRODUCT CATEGORIES' => 30,
            'PRODUCT TYPES' => 40,
            'BRANDS' => 50,
            'STOCK LOCATIONS' => 60,
            'STOCK TRANSFERS' => 70,
            'STOCK TAKES' => 80,
        ],
        'DESIGN' => [
            'INVOICE DESIGNS' => 10,
            'PAYMENT DESIGNS' => 20,
        ],
        'FIXED ASSETS' => [
            'DASHBOARD' => 10,
            'ASSET CATEGORIES' => 20,
            'ASSET REGISTER' => 30,
            'DEPRECIATION' => 35,
            'VALUATIONS' => 40,
            'WRITE OFFS' => 50,
            'ASSET LOANS' => 60,
            'MAINTENANCE' => 70,
            'REMINDER SETTINGS' => 80,
        ],
        'SALARY' => [
            'DASHBOARD' => 10,
            'SALARY' => 20,
            'TYPE' => 30,
            'ATTENDANCE' => 40,
            'BATCH' => 50,
            'COMPANY TAX INFORMATION' => 60,
            'SETTING' => 70,
        ],
    ];

    /**
     * Fetch nested menu structure for a user with pre-calculated URLs and active states.
     *
     * Visibility comes from the folder, page grouping, and page permission rows
     * for the user's role. No role is special-cased, so the sidebar always
     * reflects the same matrix that the controllers enforce.
     *
     * @param  User|null  $user  The authenticated user.
     * @return array<int, array<string, mixed>>
     */
    public function getMenusForUser(?User $user): array
    {
        if (! $user || ! $user->role_id) {
            return [];
        }

        $roleId = $user->role_id;

        /** @var Collection<int, stdClass> $folders */
        $folders = DB::table('folder_permissions')
            ->join('folders', 'folder_permissions.folder_id', '=', 'folders.id')
            ->join('roles', 'folder_permissions.role_id', '=', 'roles.id')
            ->where('folder_permissions.role_id', $roleId)
            ->where('folder_permissions.can_read', true)
            ->select(
                'folders.id',
                'folders.name',
                'folders.icon'
            )
            ->get()
            ->sortBy(function (stdClass $folder): int {
                $name = is_string($folder->name) ? $folder->name : '';

                return self::FOLDER_ORDER[$name] ?? 999;
            })
            ->values();

        if ($folders->isEmpty()) {
            return [];
        }

        $folderIds = $folders->pluck('id');

        /** @var Collection<int, stdClass> $pageGroupings */
        $pageGroupings = DB::table('page_grouping_permissions')
            ->join('page_groupings', 'page_grouping_permissions.page_grouping_id', '=', 'page_groupings.id')
            ->join('roles', 'page_grouping_permissions.role_id', '=', 'roles.id')
            ->where('page_grouping_permissions.role_id', $roleId)
            ->where('page_grouping_permissions.can_read', true)
            ->whereIn('page_groupings.folder_id', $folderIds)
            ->select(
                'page_groupings.id',
                'page_groupings.folder_id',
                'page_groupings.name',
                'page_groupings.icon'
            )
            ->get();

        /** @var Collection<int|string, Collection<int, stdClass>> $groupingsByFolder */
        $groupingsByFolder = $pageGroupings->groupBy('folder_id');

        /** @var Collection<int, stdClass> $pages */
        $pages = DB::table('page_permissions')
            ->join('pages', 'page_permissions.page_id', '=', 'pages.id')
            ->join('roles', 'page_permissions.role_id', '=', 'roles.id')
            ->where('page_permissions.role_id', $roleId)
            ->where('page_permissions.can_read', true)
            ->whereIn('pages.folder_id', $folderIds)
            ->select(
                'pages.id',
                'pages.folder_id',
                'pages.page_grouping_id',
                'pages.name',
                'pages.icon',
                'pages.route_name'
            )
            ->get();

        /** @var array<int, array<string, mixed>> $result */
        $result = $folders->map(
            /**
             * @return array<string,mixed>
             */
            function (stdClass $folder) use ($pages, $groupingsByFolder): array {
                /** @var string $folderName */
                $folderName = $folder->name;
                /** @var Collection<int, stdClass> $folderPages */
                $folderPages = $pages->where('folder_id', $folder->id);

                if (isset(self::PAGE_ORDER[$folderName])) {
                    $folderPages = $folderPages
                        ->sortBy(function (stdClass $page) use ($folderName): int|float {
                            $pageName = is_string($page->name) ? $page->name : '';

                            return self::PAGE_ORDER[$folderName][$pageName] ?? 999;
                        })
                        ->values();
                }

                /** @var int|string $folderId */
                $folderId = $folder->id;
                /** @var Collection<int, stdClass> $folderGroupings */
                $folderGroupings = $groupingsByFolder->get($folderId, collect());

                $groupings = [];

                foreach ($folderGroupings as $grouping) {
                    $groupingPages = $folderPages
                        ->filter(fn (stdClass $page): bool => $page->page_grouping_id === $grouping->id)
                        ->map(fn (stdClass $page): array => $this->mapPage($page))
                        ->values()
                        ->toArray();

                    if (! empty($groupingPages)) {
                        $groupings[] = [
                            'id' => $grouping->id,
                            'name' => $grouping->name,
                            'icon' => $grouping->icon,
                            'pages' => $groupingPages,
                        ];
                    }
                }

                $ungroupedPages = $folderPages
                    ->filter(fn (stdClass $page): bool => empty($page->page_grouping_id))
                    ->map(fn (stdClass $page): array => $this->mapPage($page))
                    ->values()
                    ->toArray();

                if (! empty($ungroupedPages)) {
                    $groupings[] = [
                        'id' => null,
                        'name' => null,
                        'icon' => null,
                        'pages' => $ungroupedPages,
                    ];
                }

                return [
                    'id' => $folder->id,
                    'name' => $folder->name,
                    'icon' => $folder->icon,
                    'groupings' => $groupings,
                ];
            }
        )->values()->toArray();

        return $result;
    }

    /**
     * Map a page record to a menu page array with URL and active state.
     *
     * @param  stdClass  $page  The page record from the database.
     * @return array<string, mixed> The mapped page array.
     */
    private function mapPage(stdClass $page): array
    {
        /** @var string $routeName */
        $routeName = $page->route_name ?? '';
        $hasRoute = $routeName !== ''
            && Route::has($routeName);

        return [
            'id' => $page->id,
            'name' => $page->name,
            'icon' => $page->icon,
            'url' => $hasRoute
                ? route($routeName)
                : '#',
            'is_active' => $routeName !== ''
                && request()->routeIs($routeName.'*'),
        ];
    }
}
