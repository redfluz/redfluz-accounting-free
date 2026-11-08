<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['receipts', 'payment_vouchers'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                foreach (['debit_chart_id', 'credit_chart_id'] as $fk) {
                    if (Schema::hasColumn($tableName, $fk)) {
                        try { $table->dropForeign([$fk]); } catch (\Throwable) {}
                    }
                }
                $cols = array_values(array_filter(
                    ['debit_chart_id', 'debit_acc', 'debit_acc_desc', 'credit_chart_id', 'credit_acc', 'credit_acc_desc'],
                    fn (string $c): bool => Schema::hasColumn($tableName, $c)
                ));
                if ($cols !== []) {
                    $table->dropColumn($cols);
                }
            });
        }
    }

    public function down(): void {}
};
