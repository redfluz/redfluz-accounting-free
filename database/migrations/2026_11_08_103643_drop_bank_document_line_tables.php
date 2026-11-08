<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('customer_refund_lines');
        Schema::dropIfExists('supplier_refund_lines');
        Schema::dropIfExists('payment_voucher_lines');
        Schema::dropIfExists('receipt_lines');
    }

    public function down(): void {}
};
