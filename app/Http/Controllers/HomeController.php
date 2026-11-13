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

namespace App\Http\Controllers;

use App\Services\DashboardCrmService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * The dashboard service is injected rather than resolved through the container
     * at the point of use, so what this controller depends on is declared where it
     * can be read and so a test can hand it a service of its own.
     */
    public function __construct(private readonly DashboardCrmService $dashboard)
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of the resource.
     * Show the application dashboard.
     *
     * @param  Request  $request  The incoming HTTP request.
     * @return View Returns the rendered Blade view.
     */
    public function index(Request $request): View
    {
        $requestedMonth = $request->input('month');
        $selectedMonth = is_string($requestedMonth) && $requestedMonth !== ''
            ? Carbon::parse($requestedMonth)->startOfMonth()
            : now()->startOfMonth();

        $data = $this->dashboard->getDashboardData($selectedMonth);

        return view('crm.dashboard.index', $data);
    }
}
