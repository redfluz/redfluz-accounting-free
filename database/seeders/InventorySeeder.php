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

use App\Enums\AcquisitionType;
use App\Enums\StockLocationType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use LogicException;

/**
 * Seed inventory master data and realistic computer and phone shop products.
 */
class InventorySeeder extends Seeder
{
    /**
     * Seed brands, a default stock location, products, and variants.
     */
    public function run(): void
    {
        $now = now();
        $brands = [
            ['name' => 'Acer', 'description' => 'Computers, notebooks, monitors, and accessories.'],
            ['name' => 'Apple', 'description' => 'Phones, tablets, computers, and accessories.'],
            ['name' => 'ASUS', 'description' => 'Computers, components, and gaming equipment.'],
            ['name' => 'Crucial', 'description' => 'Computer memory and storage products.'],
            ['name' => 'Dell', 'description' => 'Computers, workstations, and monitors.'],
            ['name' => 'HP', 'description' => 'Computers, printers, and accessories.'],
            ['name' => 'IBM', 'description' => 'Enterprise and legacy computer equipment.'],
            ['name' => 'Kingston', 'description' => 'Memory, flash storage, and solid-state drives.'],
            ['name' => 'Lenovo', 'description' => 'Computers, tablets, and accessories.'],
            ['name' => 'Logitech', 'description' => 'Computer peripherals and accessories.'],
            ['name' => 'Microsoft', 'description' => 'Computers, software, and gaming equipment.'],
            ['name' => 'Nintendo', 'description' => 'Gaming consoles and accessories.'],
            ['name' => 'Samsung', 'description' => 'Phones, tablets, monitors, and storage products.'],
            ['name' => 'Sony', 'description' => 'Gaming consoles, audio, and consumer electronics.'],
            ['name' => 'Xiaomi', 'description' => 'Phones, tablets, and smart-device accessories.'],
        ];
        $brandRows = collect($brands)->map(fn (array $brand): array => [
            'id' => (string) Str::uuid7(),
            'name' => $brand['name'],
            'description' => $brand['description'],
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
            'deleted_at' => null,
        ])->all();
        DB::table('brands')->upsert(
            $brandRows,
            ['name'],
            ['description', 'is_active', 'updated_at', 'deleted_at']
        );

        $categories = [
            ['code' => 'LAPTOP', 'name' => 'Laptops', 'description' => 'Notebook computers and business laptops.'],
            ['code' => 'MOBILE', 'name' => 'Mobile Devices', 'description' => 'Smartphones and related mobile devices.'],
            ['code' => 'GAMING', 'name' => 'Gaming', 'description' => 'Consoles, games, and gaming accessories.'],
            ['code' => 'DISPLAY', 'name' => 'Displays & Monitors', 'description' => 'Monitors, panels, and display accessories.'],
            ['code' => 'COMPONENT', 'name' => 'Components & Parts', 'description' => 'Replacement parts and computer components.'],
            ['code' => 'ACCESSORY', 'name' => 'Accessories', 'description' => 'Peripherals, adapters, and accessories.'],
            ['code' => 'SERVICE', 'name' => 'Services', 'description' => 'Repair, installation, and maintenance services.'],
        ];
        DB::table('product_categories')->upsert(
            collect($categories)->map(fn (array $category): array => [
                'id' => (string) Str::uuid7(),
                'code' => $category['code'],
                'name' => $category['name'],
                'description' => $category['description'],
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ])->all(),
            ['code'],
            ['name', 'description', 'is_active', 'updated_at', 'deleted_at']
        );

        $categoryIds = DB::table('product_categories')->pluck('id', 'code');
        $types = [
            ['category' => 'LAPTOP', 'code' => 'NOTEBOOK', 'name' => 'Notebook', 'description' => 'Portable notebook computers.'],
            ['category' => 'MOBILE', 'code' => 'SMARTPHONE', 'name' => 'Smartphone', 'description' => 'Android and iOS smartphones.'],
            ['category' => 'GAMING', 'code' => 'CONSOLE', 'name' => 'Console', 'description' => 'Handheld and home gaming consoles.'],
            ['category' => 'DISPLAY', 'code' => 'MONITOR', 'name' => 'Monitor', 'description' => 'Desktop monitors and display panels.'],
            ['category' => 'COMPONENT', 'code' => 'STORAGE', 'name' => 'Storage & Memory', 'description' => 'SSDs, hard drives, and memory modules.'],
            ['category' => 'ACCESSORY', 'code' => 'PERIPHERAL', 'name' => 'Peripheral', 'description' => 'Mice, keyboards, and other peripherals.'],
            ['category' => 'ACCESSORY', 'code' => 'CHARGER', 'name' => 'Charger & Power', 'description' => 'Chargers, adapters, and power supplies.'],
            ['category' => 'COMPONENT', 'code' => 'SPARE-PART', 'name' => 'Spare Part', 'description' => 'Replacement spare parts used in repairs.'],
            ['category' => 'SERVICE', 'code' => 'REPAIR', 'name' => 'Repair Service', 'description' => 'Hardware repair and replacement labour.'],
            ['category' => 'SERVICE', 'code' => 'INSTALLATION', 'name' => 'Installation Service', 'description' => 'Software installation and data services.'],
        ];
        DB::table('product_types')->upsert(
            collect($types)->map(fn (array $type): array => [
                'id' => (string) Str::uuid7(),
                'product_category_id' => $categoryIds->get($type['category']),
                'code' => $type['code'],
                'name' => $type['name'],
                'description' => $type['description'],
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ])->all(),
            ['code'],
            ['product_category_id', 'name', 'description', 'is_active', 'updated_at', 'deleted_at']
        );

        DB::table('stock_locations')->upsert([[
            'id' => (string) Str::uuid7(),
            'code' => 'WH-001',
            'name' => 'Default Warehouse',
            'type' => StockLocationType::WAREHOUSE->value,
            'address' => null,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
            'deleted_at' => null,
        ]], ['code'], ['name', 'type', 'address', 'is_active', 'updated_at', 'deleted_at']);

        $brandIds = DB::table('brands')->pluck('id', 'name');
        $products = [
            ['brand' => 'HP', 'name' => 'HP Pavilion 15 Laptop', 'sku' => 'HP-PAV-15', 'cost' => 2300, 'price' => 2799, 'description' => '15-inch notebook for home, study, and office use.', 'level' => 3, 'reorder' => 5],
            ['brand' => 'Lenovo', 'name' => 'Lenovo ThinkPad E14 Laptop', 'sku' => 'LEN-E14-G5', 'cost' => 2950, 'price' => 3499, 'description' => 'Business notebook with a 14-inch display.', 'level' => 3, 'reorder' => 5],
            ['brand' => 'ASUS', 'name' => 'ASUS Vivobook 15 Laptop', 'sku' => 'ASU-VIVO-15', 'cost' => 2100, 'price' => 2599, 'description' => 'Everyday 15-inch notebook computer.', 'level' => 3, 'reorder' => 5],
            ['brand' => 'Acer', 'name' => 'Acer Aspire 5 Laptop', 'sku' => 'ACE-ASP-5', 'cost' => 2250, 'price' => 2699, 'description' => 'General-purpose 15-inch notebook computer.', 'level' => 3, 'reorder' => 5],
            ['brand' => 'Apple', 'name' => 'Apple iPhone 15', 'sku' => 'APL-IP15', 'cost' => 2850, 'price' => 3299, 'description' => 'Apple smartphone available in multiple storage and colour variants.', 'level' => 4, 'reorder' => 8],
            ['brand' => 'Samsung', 'name' => 'Samsung Galaxy A55 5G', 'sku' => 'SMS-A55', 'cost' => 1450, 'price' => 1699, 'description' => 'Samsung 5G smartphone available in multiple storage variants.', 'level' => 4, 'reorder' => 8],
            ['brand' => 'Xiaomi', 'name' => 'Xiaomi Redmi Note 13 5G', 'sku' => 'XMI-RN13', 'cost' => 850, 'price' => 1099, 'description' => 'Affordable 5G Android smartphone.', 'level' => 5, 'reorder' => 10],
            ['brand' => 'Nintendo', 'name' => 'Nintendo Switch OLED', 'sku' => 'NTD-SW-OLED', 'cost' => 1250, 'price' => 1499, 'description' => 'Handheld gaming console with OLED display.', 'level' => 2, 'reorder' => 4],
            ['brand' => 'Sony', 'name' => 'Sony PlayStation 5 Slim', 'sku' => 'SNY-PS5-SLIM', 'cost' => 1950, 'price' => 2299, 'description' => 'Slim edition home gaming console.', 'level' => 2, 'reorder' => 3],
            ['brand' => 'Dell', 'name' => 'Dell 24-inch IPS Monitor', 'sku' => 'DEL-P2422H', 'cost' => 520, 'price' => 699, 'description' => '24-inch Full HD IPS office monitor.', 'level' => 4, 'reorder' => 6],
            ['brand' => 'Logitech', 'name' => 'Logitech MX Master 3S Mouse', 'sku' => 'LOG-MX-M3S', 'cost' => 330, 'price' => 429, 'description' => 'Wireless productivity mouse.', 'level' => 5, 'reorder' => 8],
            ['brand' => 'Kingston', 'name' => 'Kingston NV2 1TB NVMe SSD', 'sku' => 'KIN-NV2-1TB', 'cost' => 210, 'price' => 289, 'description' => '1TB NVMe solid-state drive for upgrades and repairs.', 'level' => 8, 'reorder' => 15],
            ['brand' => 'Crucial', 'name' => 'Crucial 16GB DDR4 SODIMM', 'sku' => 'CRU-DDR4-16', 'cost' => 115, 'price' => 169, 'description' => '16GB notebook memory module for upgrades and repairs.', 'level' => 8, 'reorder' => 15],
            ['brand' => 'HP', 'name' => 'HP 65W Laptop Charger', 'sku' => 'HP-CHG-65W', 'cost' => 70, 'price' => 119, 'description' => 'Replacement power adapter for compatible HP laptops.', 'level' => 5, 'reorder' => 10],
            ['brand' => 'Apple', 'name' => 'Apple 20W USB-C Power Adapter', 'sku' => 'APL-USB-C-20W', 'cost' => 65, 'price' => 99, 'description' => '20W USB-C wall power adapter.', 'level' => 8, 'reorder' => 15],
            ['brand' => 'Samsung', 'name' => 'Samsung 25W USB-C Charger', 'sku' => 'SMS-USB-C-25W', 'cost' => 55, 'price' => 89, 'description' => '25W USB-C fast-charging wall adapter.', 'level' => 8, 'reorder' => 15],
            ['brand' => null, 'name' => '15.6-inch Full HD Laptop Display', 'sku' => 'PART-LCD-156-FHD', 'cost' => 210, 'price' => 320, 'description' => 'Replacement Full HD laptop display panel.', 'level' => 4, 'reorder' => 8],
            ['brand' => null, 'name' => 'Universal Laptop Cooling Fan', 'sku' => 'PART-FAN-LAP', 'cost' => 35, 'price' => 69, 'description' => 'Replacement cooling fan used during laptop repairs.', 'level' => 6, 'reorder' => 12],
            ['brand' => null, 'name' => 'USB-C Charging Port Assembly', 'sku' => 'PART-USB-C-PORT', 'cost' => 28, 'price' => 59, 'description' => 'Replacement USB-C charging port assembly for phone repairs.', 'level' => 10, 'reorder' => 20],
        ];
        $services = [
            ['name' => 'Computer Diagnostic Service', 'sku' => 'SVC-PC-DIAG', 'price' => 80, 'description' => 'Computer hardware and software fault diagnosis.'],
            ['name' => 'Laptop Screen Replacement Labour', 'sku' => 'SVC-LAP-SCREEN', 'price' => 150, 'description' => 'Labour charge for laptop display replacement.'],
            ['name' => 'Smartphone Screen Replacement Labour', 'sku' => 'SVC-PHN-SCREEN', 'price' => 120, 'description' => 'Labour charge for smartphone display replacement.'],
            ['name' => 'Smartphone Battery Replacement Labour', 'sku' => 'SVC-PHN-BATTERY', 'price' => 80, 'description' => 'Labour charge for smartphone battery replacement.'],
            ['name' => 'Data Backup and OS Installation', 'sku' => 'SVC-OS-INSTALL', 'price' => 180, 'description' => 'Customer data backup and operating system installation.'],
            ['name' => 'Game Console Cleaning Service', 'sku' => 'SVC-CONSOLE-CLEAN', 'price' => 120, 'description' => 'Internal cleaning and thermal service for game consoles.'],
        ];
        $productRows = collect($products)->map(fn (array $product): array => [
            'id' => (string) Str::uuid7(),
            'parent_product_id' => null,
            'brand_id' => is_string($product['brand']) ? $brandIds->get($product['brand']) : null,
            'name' => $product['name'],
            'sku' => $product['sku'],
            'variant_name' => null,
            'variant_details' => null,
            'inherits_selling_price' => false,
            'image_path' => null,
            'base_price' => $product['cost'],
            'selling_price' => $product['price'],
            'default_tax_id' => null,
            'default_discount_id' => null,
            'description' => $product['description'],
            'is_service' => false,
            'is_product' => true,
            'default_acquisition_type' => AcquisitionType::STOCK->value,
            'reorder_level' => $product['level'],
            'reorder_quantity' => $product['reorder'],
            'created_at' => $now,
            'updated_at' => $now,
            'deleted_at' => null,
        ])->merge(collect($services)->map(fn (array $service): array => [
            'id' => (string) Str::uuid7(),
            'parent_product_id' => null,
            'brand_id' => null,
            'name' => $service['name'],
            'sku' => $service['sku'],
            'variant_name' => null,
            'variant_details' => null,
            'inherits_selling_price' => false,
            'image_path' => null,
            'base_price' => 0,
            'selling_price' => $service['price'],
            'default_tax_id' => null,
            'default_discount_id' => null,
            'description' => $service['description'],
            'is_service' => true,
            'is_product' => false,
            'default_acquisition_type' => AcquisitionType::EXPENSE->value,
            'reorder_level' => 0,
            'reorder_quantity' => 0,
            'created_at' => $now,
            'updated_at' => $now,
            'deleted_at' => null,
        ]))->all();
        $categoryIdByType = DB::table('product_types')
            ->join('product_categories', 'product_categories.id', '=', 'product_types.product_category_id')
            ->get(['product_types.id', 'product_types.code', 'product_categories.id as category_id'])
            ->mapWithKeys(fn ($type): array => [$type->code => [
                'type_id' => $type->id,
                'category_id' => $type->category_id,
            ]]);
        $productSkuTypes = [
            'HP-PAV-15' => 'NOTEBOOK',
            'LEN-E14-G5' => 'NOTEBOOK',
            'ASU-VIVO-15' => 'NOTEBOOK',
            'ACE-ASP-5' => 'NOTEBOOK',
            'APL-IP15' => 'SMARTPHONE',
            'SMS-A55' => 'SMARTPHONE',
            'XMI-RN13' => 'SMARTPHONE',
            'NTD-SW-OLED' => 'CONSOLE',
            'SNY-PS5-SLIM' => 'CONSOLE',
            'DEL-P2422H' => 'MONITOR',
            'LOG-MX-M3S' => 'PERIPHERAL',
            'KIN-NV2-1TB' => 'STORAGE',
            'CRU-DDR4-16' => 'STORAGE',
            'HP-CHG-65W' => 'CHARGER',
            'APL-USB-C-20W' => 'CHARGER',
            'SMS-USB-C-25W' => 'CHARGER',
            'PART-LCD-156-FHD' => 'SPARE-PART',
            'PART-FAN-LAP' => 'SPARE-PART',
            'PART-USB-C-PORT' => 'SPARE-PART',
            'SVC-PC-DIAG' => 'REPAIR',
            'SVC-LAP-SCREEN' => 'REPAIR',
            'SVC-PHN-SCREEN' => 'REPAIR',
            'SVC-PHN-BATTERY' => 'REPAIR',
            'SVC-OS-INSTALL' => 'INSTALLATION',
            'SVC-CONSOLE-CLEAN' => 'REPAIR',
        ];
        $productTypeIds = DB::table('product_types')->pluck('id', 'code');
        $productCategoryIds = DB::table('product_categories')->pluck('id', 'code');
        $typeToCategory = DB::table('product_types')->pluck('product_category_id', 'id');

        DB::table('products')->upsert($productRows, ['sku'], [
            'brand_id', 'name', 'base_price', 'selling_price', 'description', 'is_service', 'is_product',
            'default_acquisition_type', 'reorder_level', 'reorder_quantity', 'updated_at', 'deleted_at',
        ]);

        foreach ($productSkuTypes as $sku => $typeCode) {
            $typeId = $productTypeIds->get($typeCode);

            if ($typeId === null) {
                continue;
            }

            DB::table('products')
                ->where('sku', $sku)
                ->update([
                    'product_type_id' => $typeId,
                    'product_category_id' => $typeToCategory->get($typeId),
                    'updated_at' => $now,
                ]);
        }

        $variants = [
            ['parent' => 'APL-IP15', 'name' => '128GB Black', 'sku' => 'APL-IP15-128-BLK', 'details' => 'Storage: 128GB, Colour: Black', 'cost' => 2850, 'price' => 3299],
            ['parent' => 'APL-IP15', 'name' => '128GB Blue', 'sku' => 'APL-IP15-128-BLU', 'details' => 'Storage: 128GB, Colour: Blue', 'cost' => 2850, 'price' => 3299],
            ['parent' => 'APL-IP15', 'name' => '256GB Black', 'sku' => 'APL-IP15-256-BLK', 'details' => 'Storage: 256GB, Colour: Black', 'cost' => 3250, 'price' => 3799],
            ['parent' => 'SMS-A55', 'name' => '128GB Navy', 'sku' => 'SMS-A55-128-NVY', 'details' => 'Storage: 128GB, Colour: Navy', 'cost' => 1450, 'price' => 1699],
            ['parent' => 'SMS-A55', 'name' => '256GB Lilac', 'sku' => 'SMS-A55-256-LIL', 'details' => 'Storage: 256GB, Colour: Lilac', 'cost' => 1620, 'price' => 1899],
            ['parent' => 'NTD-SW-OLED', 'name' => 'White', 'sku' => 'NTD-SW-OLED-WHT', 'details' => 'Dock and Joy-Con colour: White', 'cost' => 1250, 'price' => 1499],
            ['parent' => 'NTD-SW-OLED', 'name' => 'Neon Red and Blue', 'sku' => 'NTD-SW-OLED-NEON', 'details' => 'Joy-Con colour: Neon Red and Neon Blue', 'cost' => 1250, 'price' => 1499],
        ];
        $parentProducts = DB::table('products')
            ->whereIn('sku', collect($variants)->pluck('parent')->unique())
            ->whereNull('deleted_at')
            ->get(['id', 'brand_id', 'name', 'sku'])
            ->keyBy('sku');
        $variantRows = collect($variants)->map(function (array $variant) use ($now, $parentProducts): array {
            $parent = $parentProducts->get($variant['parent']);
            if ($parent === null) {
                throw new LogicException('The parent product '.$variant['parent'].' is required.');
            }

            return [
                'id' => (string) Str::uuid7(),
                'parent_product_id' => $parent->id,
                'brand_id' => $parent->brand_id,
                'name' => $parent->name.' - '.$variant['name'],
                'sku' => $variant['sku'],
                'variant_name' => $variant['name'],
                'variant_details' => $variant['details'],
                'inherits_selling_price' => false,
                'image_path' => null,
                'base_price' => $variant['cost'],
                'selling_price' => $variant['price'],
                'default_tax_id' => null,
                'default_discount_id' => null,
                'description' => $variant['details'],
                'is_service' => false,
                'is_product' => true,
                'default_acquisition_type' => AcquisitionType::STOCK->value,
                'reorder_level' => 3,
                'reorder_quantity' => 6,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ];
        })->all();
        DB::table('products')->upsert($variantRows, ['sku'], [
            'parent_product_id', 'brand_id', 'name', 'variant_name', 'variant_details', 'base_price',
            'selling_price', 'description', 'reorder_level', 'reorder_quantity', 'updated_at', 'deleted_at',
        ]);

        DB::table('products')->whereIn('sku', [
            'HW-TRADING-WS', 'SW-PRO-TRADER', 'SVC-COMM-INST', 'APL-IP15-128', 'SMS-A55-128',
        ])->update(['deleted_at' => $now, 'updated_at' => $now]);
    }
}
