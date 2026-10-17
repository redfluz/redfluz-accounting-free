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

namespace App\Http\Controllers\System;

use App\Constants\FlashMessage;
use App\Enums\FileResources;
use App\Http\Controllers\Controller;
use App\Http\Requests\System\SystemSetting\UpdateSystemSettingRequest;
use App\Models\Finance\Currency;
use App\Models\System\Setting;
use App\Support\Data\System\SystemSettingData;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Basic company profile for the single settings row.
 *
 * This is a singleton: there is exactly one row, created on first visit. It holds
 * only the company identity that appears on documents (invoices, purchase orders,
 * quotations, PDFs) and in the sidebar. Tax filing, RMA, mail and timezone
 * configuration are deliberately absent — none of them are used by the
 * application.
 */
class SystemSettingController extends Controller
{
    /**
     * Display the settings form for the singleton row.
     *
     * @return View Returns the rendered Blade view.
     */
    public function index(): View
    {
        $this->allows(FileResources::SETTINGS);

        $setting = Setting::query()->first() ?? new Setting;

        /** @var Collection<int, Currency> $currencies */
        $currencies = Currency::query()->where('is_active', true)->orderBy('name')->get();

        return view('system.settings.index', compact('setting', 'currencies'));
    }

    /**
     * Update the singleton settings row.
     *
     * @param  UpdateSystemSettingRequest  $request  The validated settings being saved.
     * @return RedirectResponse Redirects to the specified route.
     *
     * @throws Throwable
     */
    public function update(UpdateSystemSettingRequest $request): RedirectResponse
    {
        $data = SystemSettingData::from($request->validated());

        $setting = Setting::query()->first();

        if ($request->hasFile('logo')) {
            $this->deleteLogo($setting);
            $data->logoPath = $this->storeUpload($request->file('logo'), 'settings', self::PUBLIC_DISK);
        } else {
            // The logo path is not part of the form validation; preserve the
            // existing value when no new file is uploaded.
            $data->logoPath = $setting?->logo_path;
        }

        DB::transaction(function () use ($setting, $data): void {
            if ($setting === null) {
                Setting::query()->create($data->toCreateRow());

                return;
            }

            $setting->update($data->toRow());
        });

        return redirect()
            ->route('system.settings.index')
            ->with('success', FlashMessage::updated(FileResources::SETTINGS));
    }

    /**
     * Remove the stored logo file, if one exists.
     *
     * @param  Setting|null  $setting  The settings row, when it already exists.
     */
    private function deleteLogo(?Setting $setting): void
    {
        if ($setting !== null && $setting->logo_path !== null && Storage::disk('public')->exists($setting->logo_path)) {
            Storage::disk('public')->delete($setting->logo_path);
        }
    }
}
