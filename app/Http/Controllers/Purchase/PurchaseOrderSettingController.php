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

namespace App\Http\Controllers\Purchase;

use App\Enums\FileResources;
use App\Http\Controllers\Controller;
use App\Http\Requests\Purchase\PurchaseOrderSetting\UpdatePurchaseOrderSettingRequest;
use App\Models\Purchase\PurchaseOrderSetting;
use App\Models\System\Role;
use App\Support\Data\Purchase\PurchaseOrderSettingData;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

class PurchaseOrderSettingController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return View Returns the rendered Blade view.
     */
    public function index(): View
    {
        $this->allows(FileResources::PURCHASE_ORDER_SETTINGS);
        $setting = PurchaseOrderSetting::query()->first()
            ?? new PurchaseOrderSetting([
                'default_credit_term_days' => 30,
                'default_required_days' => 5,
                'default_rfq_response_days' => 30,
            ]);
        /** @var Collection<int, Role> $roles */
        $roles = Role::query()->orderBy('name')->get();

        return view('purchase.purchase-order-settings.index', compact('setting', 'roles'));
    }

    /**
     * Update an existing resource by its object.
     *
     * @param  UpdatePurchaseOrderSettingRequest  $request  The validated request instance.
     * @return RedirectResponse Redirects to the specified route.
     *
     * @throws Throwable
     */
    public function update(UpdatePurchaseOrderSettingRequest $request): RedirectResponse
    {
        $data = PurchaseOrderSettingData::from($request->validated());

        DB::transaction(function () use ($data): void {
            PurchaseOrderSetting::query()->updateOrCreate([], [
                'default_credit_term_days' => $data->defaultCreditTermDays,
                'default_required_days' => $data->defaultRequiredDays,
                'default_rfq_response_days' => $data->defaultRfqResponseDays,
            ]);
        });

        return redirect()->route('purchase.purchase-order-settings.index')
            ->with('success', __('purchase_order_settings.messages.updated'));
    }
}
