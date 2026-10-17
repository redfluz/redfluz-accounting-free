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

return [
    'title' => 'Invois',
    'subtitle' => 'Urus invois jualan dan belian untuk satu produk.',
    'table' => [
        'column_entry' => 'Entri',
    ],
    'form' => [
        'create_title' => 'Cipta Invois',
        'edit_title' => 'Sunting Invois Jualan',
        'accounting_hint' => 'Biarkan akaun kosong untuk menggunakan akaun carta Tetapan yang dikonfigurasikan.',
        'quick_customer_name' => 'Nama pelanggan',
        'quick_supplier_name' => 'Nama pembekal',
    ],
    'sub_type' => [
        'sales_invoice' => 'Invois Jualan',
        'purchase_invoice' => 'Invois Belian',
    ],
    'actions' => [
        'quick_customer' => 'Tambah pelanggan segera',
        'add_customer' => 'Tambah Pelanggan',
        'quick_supplier' => 'Tambah pembekal segera',
        'add_supplier' => 'Tambah Pembekal',
    ],
    'messages' => [
        'design_required' => 'Cipta reka bentuk invois untuk jenis dokumen ini sebelum mencetak.',
    ],
    'deduct_inventory' => [
        'label' => 'Kurangi Inventori',
        'help' => 'Kurangkan inventori apabila invois ini diposting. Biarkan kosong untuk jualan kredit whereby barang masih di gudang dan pesanan penghantaran akan mengurangkan inventori semasa dihantar.',
        'confirm' => 'Kurangkan inventori untuk invois ini? Tindakan ini hanya boleh dilakukan sekali dan tidak boleh dibatalkan dengan menyunting invois.',
    ],
    'validation' => [
        'accounts_must_differ' => 'Akaun debit dan kredit mestilah berbeza.',
        'contact_company_invalid' => 'Kenalan yang dipilih bukan milik syarikat yang dipilih.',
        'debit_account_invalid' => 'Akaun debit yang dipilih tidak sah untuk jenis invois ini.',
        'credit_account_invalid' => 'Akaun kredit yang dipilih tidak sah untuk jenis invois ini.',
        'attachment_type_invalid' => 'Lampiran mestilah fail PNG, JPG, JPEG, PDF, DOC atau DOCX.',
        'company_name_required' => 'Masukkan nama syarikat.',
        'company_create_failed' => 'Syarikat tidak dapat dicipta. Sila cuba lagi.',
    ],
    'pdf' => [
        'sales_invoice_title' => 'INVOIS JUALAN',
        'purchase_invoice_title' => 'INVOIS BELIAN',
        'company' => 'Syarikat',
        'date' => 'Tarikh',
        'description' => 'Penerangan',
        'quantity' => 'Kuantiti',
        'unit_price' => 'Harga Seunit',
        'amount' => 'Amaun',
        'total' => 'Jumlah',
        'tax_number' => 'Nombor Cukai',
        'payment_details' => 'Maklumat Pembayaran',
        'bank_details_placeholder' => 'Sila rujuk arahan pembayaran syarikat.',
        'company_placeholder' => 'Nama Syarikat',
    ],
];
