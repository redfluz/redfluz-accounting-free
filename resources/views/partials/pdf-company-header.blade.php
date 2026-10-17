{{--
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
--}}

@php
    $company = $setting ?? null;
@endphp

@if ($company)
    <div class="company-header">
        <div class="company-name">{{ $company->company_name }}</div>
        @if ($company->address)
            <div class="company-info">{{ $company->address }}</div>
        @endif
        @if ($company->company_phone)
            <div class="company-info">Tel: {{ $company->company_phone }}</div>
        @endif
        @if ($company->company_email)
            <div class="company-info">{{ $company->company_email }}</div>
        @endif
    </div>
@endif
