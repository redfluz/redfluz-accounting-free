<?php

use App\Enums\Finance\DocumentStatus;
use App\Enums\Finance\LedgerSubType;
use App\Enums\Finance\LedgerType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Manual adjustments: COA-based debit/credit notes (not product-based).
     */
    public function up(): void
    {
        Schema::create('manual_sales_adjustments', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->nullable()->index();
            $table->foreignUuid('contact_id')->nullable()->index();
            $table->string('document_number')->unique();
            $table->enum('subtype', [
                LedgerSubType::INCREASE_ADJUSTMENT->value,
                LedgerSubType::DECREASE_ADJUSTMENT->value,
                LedgerSubType::MANUAL_SALES_ADJUSTMENT->value,
                LedgerSubType::SALES_DEBIT_NOTE->value,
                LedgerSubType::SALES_CREDIT_NOTE->value,
            ]);
            $table->string('reference_number')->nullable()->index();
            $table->string('document_original')->nullable();
            $table->foreignUuid('sales_invoice_id')->nullable()->constrained('sales_invoices')->nullOnDelete();
            $table->string('company_name')->nullable();
            $table->string('contact_name')->nullable();
            $table->date('date');
            $table->enum('status', array_column(DocumentStatus::cases(), 'value'))
                ->default(DocumentStatus::PENDING->value);
            $table->string('attachment_path')->nullable();
            $table->decimal('subtotal', 15)->default(0);
            $table->decimal('total_amount', 15)->default(0);
            $table->text('notes')->nullable();

            $table->uuid('created_by')->nullable();
            $table->string('created_name')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->string('updated_name')->nullable();
            $table->uuid('deleted_by')->nullable();
            $table->string('deleted_name')->nullable();
            $table->uuid('approved_by')->nullable();
            $table->string('approved_name')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->uuid('cancelled_by')->nullable();
            $table->string('cancelled_name')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->uuid('undone_by')->nullable();
            $table->string('undone_name')->nullable();
            $table->timestamp('undone_at')->nullable();

            $table->timestamps();
            $table->softDeletes();
            $table->index('date');
            $table->index('sales_invoice_id');
        });

        Schema::create('manual_sales_adjustment_charts', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('manual_sales_adjustment_id')->constrained('manual_sales_adjustments')->cascadeOnDelete();
            $table->foreignUuid('chart_id')->nullable()->constrained('charts');
            $table->foreignUuid('company_id')->nullable()->constrained('companies');
            $table->foreignUuid('currency_id')->nullable()->constrained('currencies');
            $table->enum('type', array_column(LedgerType::cases(), 'value'));
            $table->enum('subtype', array_column(LedgerSubType::cases(), 'value'));
            $table->string('document_number');
            $table->string('reference_number')->nullable();
            $table->date('date');
            $table->string('chart_code')->nullable();
            $table->string('chart_name')->nullable();
            $table->string('company_name')->nullable();
            $table->text('description')->nullable();
            $table->text('line_description')->nullable();
            $table->decimal('debit_amount', 15)->default(0.00);
            $table->decimal('credit_amount', 15)->default(0.00);
            $table->timestamps();
            $table->softDeletes();

            $table->index('manual_sales_adjustment_id');
            $table->index('document_number');
            $table->index('reference_number');
            $table->index('date');
            $table->index('chart_id');
            $table->index('company_id');
        });

        Schema::create('manual_purchase_adjustments', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->nullable()->index();
            $table->foreignUuid('contact_id')->nullable()->index();
            $table->string('document_number')->unique();
            $table->enum('subtype', [
                LedgerSubType::INCREASE_ADJUSTMENT->value,
                LedgerSubType::DECREASE_ADJUSTMENT->value,
                LedgerSubType::MANUAL_PURCHASE_ADJUSTMENT->value,
                LedgerSubType::PURCHASE_DEBIT_NOTE->value,
                LedgerSubType::PURCHASE_CREDIT_NOTE->value,
            ]);
            $table->string('reference_number')->nullable()->index();
            $table->string('document_original')->nullable();
            $table->foreignUuid('purchase_invoice_id')->nullable()->constrained('purchase_invoices')->nullOnDelete();
            $table->string('company_name')->nullable();
            $table->string('contact_name')->nullable();
            $table->date('date');
            $table->enum('status', array_column(DocumentStatus::cases(), 'value'))
                ->default(DocumentStatus::PENDING->value);
            $table->string('attachment_path')->nullable();
            $table->decimal('subtotal', 15)->default(0);
            $table->decimal('total_amount', 15)->default(0);
            $table->text('notes')->nullable();

            $table->uuid('created_by')->nullable();
            $table->string('created_name')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->string('updated_name')->nullable();
            $table->uuid('deleted_by')->nullable();
            $table->string('deleted_name')->nullable();
            $table->uuid('approved_by')->nullable();
            $table->string('approved_name')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->uuid('cancelled_by')->nullable();
            $table->string('cancelled_name')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->uuid('undone_by')->nullable();
            $table->string('undone_name')->nullable();
            $table->timestamp('undone_at')->nullable();

            $table->timestamps();
            $table->softDeletes();
            $table->index('date');
            $table->index('purchase_invoice_id');
        });

        Schema::create('manual_purchase_adjustment_charts', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('manual_purchase_adjustment_id')->constrained('manual_purchase_adjustments')->cascadeOnDelete();
            $table->foreignUuid('chart_id')->nullable()->constrained('charts');
            $table->foreignUuid('company_id')->nullable()->constrained('companies');
            $table->foreignUuid('currency_id')->nullable()->constrained('currencies');
            $table->enum('type', array_column(LedgerType::cases(), 'value'));
            $table->enum('subtype', array_column(LedgerSubType::cases(), 'value'));
            $table->string('document_number');
            $table->string('reference_number')->nullable();
            $table->date('date');
            $table->string('chart_code')->nullable();
            $table->string('chart_name')->nullable();
            $table->string('company_name')->nullable();
            $table->text('description')->nullable();
            $table->text('line_description')->nullable();
            $table->decimal('debit_amount', 15)->default(0.00);
            $table->decimal('credit_amount', 15)->default(0.00);
            $table->timestamps();
            $table->softDeletes();

            $table->index('manual_purchase_adjustment_id');
            $table->index('document_number');
            $table->index('reference_number');
            $table->index('date');
            $table->index('chart_id');
            $table->index('company_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manual_purchase_adjustment_charts');
        Schema::dropIfExists('manual_purchase_adjustments');
        Schema::dropIfExists('manual_sales_adjustment_charts');
        Schema::dropIfExists('manual_sales_adjustments');
    }
};
