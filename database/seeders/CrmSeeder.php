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

class CrmSeeder extends Seeder
{
    public function run(): void
    {
        $companies = [
            [
                'name' => 'Apex Capital Advisory Ltd',
                'email' => 'trades@apexcapital.com',
                'phone' => '+603-2160-1122',
                'address' => 'Suite 18-2, Menara Maybank, Jalan Tun Perak, 50050 Kuala Lumpur',
                'is_debtor' => true,
                'contacts' => [
                    ['legacy_email' => 'davidtan', 'email' => 'amir.rahman', 'name' => 'Amir Rahman', 'phone' => '+6012-310 1122', 'position' => 'Investment Director'],
                    ['legacy_email' => 'sarahlee', 'email' => 'melissa.wong', 'name' => 'Melissa Wong', 'phone' => '+6017-310 1123', 'position' => 'Finance Manager'],
                ],
            ],
            [
                'name' => 'Horizon Wealth Management',
                'email' => 'clientdesk@horizonwealth.my',
                'phone' => '+604-2200-3344',
                'address' => 'Level 10, Gurney Tower, Persiaran Gurney, 10250 Penang',
                'is_debtor' => true,
                'contacts' => [
                    ['legacy_email' => 'davidtan', 'email' => 'sarah.lee', 'name' => 'Sarah Lee', 'phone' => '+6012-420 3344', 'position' => 'Senior Portfolio Manager'],
                    ['legacy_email' => 'sarahlee', 'email' => 'farid.iskandar', 'name' => 'Farid Iskandar', 'phone' => '+6017-420 3345', 'position' => 'Client Operations Lead'],
                ],
            ],
            [
                'name' => 'Khoo & Sons Private Investment',
                'email' => 'admin@khooinvest.com',
                'phone' => '+607-3300-5566',
                'address' => 'No. 42, Jalan Molek 1/29, Taman Molek, 81100 Johor Bahru',
                'is_debtor' => true,
                'contacts' => [
                    ['legacy_email' => 'davidtan', 'email' => 'joanne.khoo', 'name' => 'Joanne Khoo', 'phone' => '+6012-730 5566', 'position' => 'Managing Partner'],
                    ['legacy_email' => 'sarahlee', 'email' => 'daniel.lim', 'name' => 'Daniel Lim', 'phone' => '+6017-730 5567', 'position' => 'Accounts Executive'],
                ],
            ],
            [
                'name' => 'Tradenet Financial Terminals',
                'email' => 'support@tradenet.com',
                'phone' => '+603-7700-8899',
                'address' => 'Wisma Tradenet, Damansara Uptown, 47400 Petaling Jaya, Selangor',
                'is_debtor' => true,
                'contacts' => [
                    ['legacy_email' => 'davidtan', 'email' => 'kelvin.ong', 'name' => 'Kelvin Ong', 'phone' => '+6012-770 8899', 'position' => 'Commercial Director'],
                    ['legacy_email' => 'sarahlee', 'email' => 'priya.nair', 'name' => 'Priya Nair', 'phone' => '+6017-770 8898', 'position' => 'Technical Account Manager'],
                ],
            ],
        ];

        foreach ($companies as $compData) {
            $companyId = DB::table('companies')->where('name', $compData['name'])->value('id') ?? (string) Str::uuid7();

            DB::table('companies')->updateOrInsert(
                ['name' => $compData['name']],
                [
                    'id' => $companyId,
                    'email' => $compData['email'],
                    'phone' => $compData['phone'],
                    'address' => $compData['address'],
                    'is_debtor' => $compData['is_debtor'],
                    'is_creditor' => false,
                    'is_courier' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            $domain = Str::after($compData['email'], '@');
            $contactIds = [];
            foreach ($compData['contacts'] as $contactData) {
                $email = $contactData['email'].'@'.$domain;
                $legacyEmail = $contactData['legacy_email'].'@'.$domain;
                $existingContact = DB::table('contacts')
                    ->where('company_id', $companyId)
                    ->whereIn('email', [$email, $legacyEmail])
                    ->first(['id', 'created_at']);
                $contactId = $existingContact->id ?? (string) Str::uuid7();

                DB::table('contacts')->updateOrInsert(
                    ['id' => $contactId],
                    [
                        'company_id' => $companyId,
                        'name' => $contactData['name'],
                        'email' => $email,
                        'phone' => $contactData['phone'],
                        'position' => $contactData['position'],
                        'created_at' => $existingContact->created_at ?? now(),
                        'updated_at' => now(),
                        'deleted_at' => null,
                    ]
                );
                $contactIds[] = $contactId;
            }
        }

        $supplierCompanies = [
            [
                'name' => 'VSTECS Berhad',
                'email' => 'enquiry@vstecs.com.my',
                'phone' => '+603-6286 8222',
                'address' => 'Lot 3, Jalan Teknologi 3/5, Taman Sains Selangor, Kota Damansara, 47810 Petaling Jaya, Selangor, Malaysia',
                'contact_name' => 'General Enquiries',
                'contact_position' => 'General Enquiries',
            ],
            [
                'name' => 'Ingram Micro Malaysia Sdn Bhd',
                'email' => 'enquiry.my@ingrammicro.com',
                'phone' => '+603-7952 8188',
                'address' => 'Lot 4A, 4th Floor, Wisma Academy, Jalan 19/1, 46300 Petaling Jaya, Selangor, Malaysia',
                'contact_name' => 'Sales Enquiries',
                'contact_position' => 'Sales Department',
            ],
            [
                'name' => 'SNS Network (M) Sdn Bhd',
                'email' => 'online@sns.com.my',
                'phone' => '+605-2424 616',
                'address' => 'No. 61, Jalan Sultan Nazrin Shah, 30250 Ipoh, Perak, Malaysia',
                'contact_name' => 'Online Sales',
                'contact_position' => 'Sales Department',
            ],
            [
                'name' => 'Kumpulan Wang Simpanan Pekerja (KWSP)',
                'email' => 'majikan@kwsp.gov.my',
                'phone' => '+603-8922 6000',
                'address' => 'Bangunan KWSP, Jalan Raja Laut, 50350 Kuala Lumpur, Malaysia',
                'contact_name' => 'Unit Caruman Majikan',
                'contact_position' => 'Statutory Contributions',
            ],
            [
                'name' => 'Pertubuhan Keselamatan Sosial (PERKESO / SOCSO)',
                'email' => 'perkeso@perkeso.gov.my',
                'phone' => '+603-4264 5555',
                'address' => 'Menara PERKESO, Jalan Ampang, 50538 Kuala Lumpur, Malaysia',
                'contact_name' => 'Unit Caruman PERKESO',
                'contact_position' => 'Statutory Contributions',
            ],
            [
                'name' => 'Lembaga Hasil Dalam Negeri Malaysia (LHDN)',
                'email' => 'ccc@hasil.gov.my',
                'phone' => '+603-8911 1000',
                'address' => 'Menara Hasil, Persiaran Rimba Permai, Cyberjaya, Selangor, Malaysia',
                'contact_name' => 'Unit Majikan dan PCB',
                'contact_position' => 'Statutory Contributions',
            ],
            [
                'name' => 'Human Resources Development Corporation (HRD Corp)',
                'email' => 'support@hrdcorp.gov.my',
                'phone' => '+603-2096 4800',
                'address' => 'Wisma HRD Corp, Jalan Beringin, Damansara Heights, 50490 Kuala Lumpur, Malaysia',
                'contact_name' => 'Unit Levi HRD Corp',
                'contact_position' => 'Statutory Contributions',
            ],
            [
                'name' => 'Perbadanan Tabung Pendidikan Tinggi Nasional (PTPTN)',
                'email' => 'careline@ptptn.gov.my',
                'phone' => '+603-2193 3000',
                'address' => 'Menara PTPTN, Jalan Yap Kwan Seng, 50450 Kuala Lumpur, Malaysia',
                'contact_name' => 'Unit Potongan Gaji',
                'contact_position' => 'Payroll Deductions',
            ],
        ];
        $existingSupplierIds = DB::table('companies')
            ->whereIn('name', collect($supplierCompanies)->pluck('name'))
            ->pluck('id', 'name');
        $now = now();
        $supplierRows = collect($supplierCompanies)->map(fn (array $company): array => [
            'id' => $existingSupplierIds->get($company['name']) ?? (string) Str::uuid7(),
            'name' => $company['name'],
            'email' => $company['email'],
            'phone' => $company['phone'],
            'address' => $company['address'],
            'is_debtor' => false,
            'is_creditor' => true,
            'is_courier' => false,
            'created_at' => $now,
            'updated_at' => $now,
            'deleted_at' => null,
        ])->all();

        DB::table('companies')->upsert($supplierRows, ['id'], [
            'name', 'email', 'phone', 'address', 'is_debtor', 'is_creditor', 'is_courier', 'updated_at', 'deleted_at',
        ]);

        $supplierCompanyIds = DB::table('companies')
            ->whereIn('name', collect($supplierCompanies)->pluck('name'))
            ->pluck('id', 'name');
        $existingContactIds = DB::table('contacts')
            ->whereIn('email', collect($supplierCompanies)->pluck('email'))
            ->pluck('id', 'email');
        $supplierContactRows = collect($supplierCompanies)->map(fn (array $company): array => [
            'id' => $existingContactIds->get($company['email']) ?? (string) Str::uuid7(),
            'company_id' => $supplierCompanyIds->get($company['name']),
            'name' => $company['contact_name'],
            'email' => $company['email'],
            'phone' => $company['phone'],
            'position' => $company['contact_position'],
            'created_at' => $now,
            'updated_at' => $now,
            'deleted_at' => null,
        ])->all();

        DB::table('contacts')->upsert($supplierContactRows, ['id'], [
            'company_id', 'name', 'email', 'phone', 'position', 'updated_at', 'deleted_at',
        ]);

        $courierCompanies = [
            [
                'name' => 'DHL Express Malaysia',
                'email' => 'service.my@dhl.com',
                'phone' => '+603-2241 8000',
                'address' => 'Level 27, Menara TM, Jalan Pantai Baharu, 50672 Kuala Lumpur, Malaysia',
            ],
            [
                'name' => 'J&T Express Malaysia',
                'email' => 'support@jtexpress.my',
                'phone' => '+603-9212 9660',
                'address' => 'Kuala Lumpur, Malaysia',
            ],
            [
                'name' => 'Ninja Van Malaysia',
                'email' => 'support_my@ninjavan.co',
                'phone' => '+6011-1722 5600',
                'address' => 'Shah Alam, Selangor, Malaysia',
            ],
        ];
        $existingCourierIds = DB::table('companies')
            ->whereIn('name', collect($courierCompanies)->pluck('name'))
            ->pluck('id', 'name');
        $courierRows = collect($courierCompanies)->map(fn (array $company): array => [
            'id' => $existingCourierIds->get($company['name']) ?? (string) Str::uuid7(),
            'name' => $company['name'],
            'email' => $company['email'],
            'phone' => $company['phone'],
            'address' => $company['address'],
            // A courier is not a goods supplier. It is flagged only as a courier so
            // that the delivery order picker can find it. Leaving is_creditor true
            // would put DHL, J&T and Ninja Van into every purchase module supplier
            // list, because those lists select on is_creditor alone.
            'is_debtor' => false,
            'is_creditor' => false,
            'is_courier' => true,
            'created_at' => $now,
            'updated_at' => $now,
            'deleted_at' => null,
        ])->all();

        DB::table('companies')->upsert($courierRows, ['id'], [
            'name', 'email', 'phone', 'address', 'is_debtor', 'is_creditor', 'is_courier', 'updated_at', 'deleted_at',
        ]);

        $addressCompanies = collect([...$companies, ...$supplierCompanies, ...$courierCompanies]);
        $companyIds = DB::table('companies')
            ->whereIn('name', $addressCompanies->pluck('name'))
            ->pluck('id', 'name');

        foreach ($addressCompanies as $company) {
            $companyId = $companyIds->get($company['name']);
            if ($companyId === null) {
                continue;
            }

            $addressId = DB::table('company_addresses')
                ->where('company_id', $companyId)
                ->where('name', 'Main Address')
                ->value('id') ?? (string) Str::uuid7();

            DB::table('company_addresses')->updateOrInsert(
                ['id' => $addressId],
                [
                    'company_id' => $companyId,
                    'name' => 'Main Address',
                    'phone' => $company['phone'],
                    'address' => $company['address'],
                    'is_default' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                    'deleted_at' => null,
                ]
            );
        }

    }
}
