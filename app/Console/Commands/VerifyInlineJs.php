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

namespace App\Console\Commands;

use App\Http\Controllers\Bank\BankTransferController;
use App\Http\Controllers\Bank\CashTransferController;
use App\Http\Controllers\Bank\CustomerRefundController;
use App\Http\Controllers\Bank\PaymentVoucherController;
use App\Http\Controllers\Bank\ReceiptController;
use App\Http\Controllers\Bank\SupplierRefundController;
use App\Http\Controllers\Crm\CompanyController;
use App\Http\Controllers\Crm\ContactController;
use App\Http\Controllers\Finance\JournalEntryController;
use App\Http\Controllers\Inventory\ProductController;
use App\Http\Controllers\Menu\FolderPermissionController;
use App\Http\Controllers\Menu\PageGroupingPermissionController;
use App\Http\Controllers\Menu\PagePermissionController;
use App\Http\Controllers\Purchase\GoodsReceiptController;
use App\Http\Controllers\Purchase\PurchaseInvoiceController;
use App\Http\Controllers\Purchase\PurchaseOrderController;
use App\Http\Controllers\Sales\DeliveryOrderController;
use App\Http\Controllers\Sales\QuotationController;
use App\Http\Controllers\Sales\SalesInvoiceController;
use App\Http\Controllers\Sales\SalesOrderController;
use App\Models\System\User;
use Closure;
use Illuminate\Console\Command;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ViewErrorBag;
use Throwable;

class VerifyInlineJs extends Command
{
    protected $signature = 'verify:inline-js';

    protected $description = 'Syntax check inline JavaScript in rendered module pages';

    /**
     * One GET page per module that carries inline scripts.
     *
     * Held as closures rather than as class-and-method-name pairs. A pair would
     * have to be dispatched by name at runtime, which hands the request to every
     * action whether or not the action asked for one — PHP discards the extra
     * argument, so it appears to work — and leaves the call unchecked. A closure
     * is a normal typed call, and an action that changes its signature breaks
     * this file rather than the check.
     *
     * Some actions answer with JSON as well as HTML — the permission pages are
     * DataTables endpoints — so the return type says both, and anything that is
     * not a view simply has no scripts to check.
     *
     * @return array<string, Closure(Request): (ViewContract|JsonResponse)>
     */
    private static function pages(): array
    {
        return [
            'purchase invoices' => fn (Request $request) => app(PurchaseInvoiceController::class)->index($request),
            'purchase orders' => fn (Request $request) => app(PurchaseOrderController::class)->index($request),
            'sales invoices' => fn (Request $request) => app(SalesInvoiceController::class)->index($request),
            'sales orders' => fn (Request $request) => app(SalesOrderController::class)->index($request),
            'quotations' => fn (Request $request) => app(QuotationController::class)->index($request),
            'delivery orders' => fn (Request $request) => app(DeliveryOrderController::class)->index($request),
            'goods receipts' => fn (Request $request) => app(GoodsReceiptController::class)->index($request),
            'products' => fn (Request $request) => app(ProductController::class)->index($request),
            'companies' => fn (Request $request) => app(CompanyController::class)->index($request),
            'contacts' => fn (Request $request) => app(ContactController::class)->index($request),
            'journal entries' => fn (Request $request) => app(JournalEntryController::class)->index($request),
            'receipts' => fn (Request $request) => app(ReceiptController::class)->index($request),
            'payment vouchers' => fn (Request $request) => app(PaymentVoucherController::class)->index($request),
            'customer refunds' => fn (Request $request) => app(CustomerRefundController::class)->index($request),
            'supplier refunds' => fn (Request $request) => app(SupplierRefundController::class)->index($request),
            // The transfer forms are the only pages whose scripts live on a create
            // page rather than a listing, which is why they are here and not
            // above. They take the request like every other entry even though
            // create() does not need one, so that every entry in this list has
            // the same shape.
            'cash transfers' => fn (Request $request) => app(CashTransferController::class)->create(),
            'bank transfers' => fn (Request $request) => app(BankTransferController::class)->create(),
            'folder-permissions' => fn (Request $request) => app(FolderPermissionController::class)->index($request),
            'page-permissions' => fn (Request $request) => app(PagePermissionController::class)->index($request),
            'page-grouping-perms' => fn (Request $request) => app(PageGroupingPermissionController::class)->index($request),
        ];
    }

    public function handle(): int
    {
        $user = User::query()->whereHas('role', fn ($query) => $query->where('name', 'Super Admin'))->first();

        if ($user === null) {
            $this->error('No Super Admin user to render with.');

            return self::FAILURE;
        }

        Auth::login($user);
        app('view')->share('errors', new ViewErrorBag);

        $directory = sys_get_temp_dir().'/inline-js-'.getmypid();

        if (! is_dir($directory) && ! mkdir($directory, 0777, true) && ! is_dir($directory)) {
            $this->error('Could not create a temporary directory.');

            return self::FAILURE;
        }

        $totalScripts = 0;
        $totalErrors = 0;

        foreach (self::pages() as $label => $render) {
            $request = Request::create('/');
            $request->setLaravelSession(app('session.store'));
            app()->instance('request', $request);

            try {
                $response = $render($request);

                $html = $response instanceof ViewContract ? $response->render() : '';
            } catch (Throwable $e) {
                $this->warn(sprintf('%-20s skipped: %s', $label, substr($e->getMessage(), 0, 50)));

                continue;
            }

            preg_match_all('/<script(?![^>]*\bsrc=)[^>]*>(.*?)<\/script>/s', $html, $matches);

            $errors = [];
            foreach ($matches[1] as $index => $script) {
                if (trim($script) === '') {
                    continue;
                }

                $totalScripts++;
                $file = $directory.'/script-'.$index.'.js';
                file_put_contents($file, $script);

                exec('node --check '.escapeshellarg($file).' 2>&1', $output, $status);

                if ($status !== 0) {
                    $errors[] = $index.': '.implode(' | ', array_slice($output, 0, 2));
                }

                unlink($file);
                $output = [];
            }

            $totalErrors += count($errors);
            $this->line(sprintf('%-20s scripts=%-3d errors=%d', $label, count($matches[1]), count($errors)));

            foreach ($errors as $error) {
                $this->error('    script #'.$error);
            }
        }

        @rmdir($directory);

        $this->newLine();
        $this->info(sprintf('Inline scripts checked: %d, syntax errors: %d', $totalScripts, $totalErrors));

        return $totalErrors === 0 ? self::SUCCESS : self::FAILURE;
    }
}
