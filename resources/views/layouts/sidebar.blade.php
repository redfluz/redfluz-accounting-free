@php use App\Models\System\Setting; @endphp
@inject('menuService', 'App\Services\MenuServices')

@php
    $menus = $menuService->getMenusForUser(auth()->user());
    $systemSetting = Setting::first();
    $companyName = $systemSetting->company_name ?? config('app.name', 'Redfluz');
    $logoPath = $systemSetting->logo_path ? asset('storage/'.$systemSetting->logo_path) : null;
@endphp

<div class="d-flex flex-column h-100 bg-dark-sidebar">
    <!-- Brand Header -->
    <div class="sidebar-brand d-flex align-items-center px-4 py-3 border-bottom border-sidebar">
        @if ($logoPath)
            <img
                class="sidebar-logo me-3"
                src="{{ $logoPath }}"
                alt="{{ $companyName }}"
            />
        @else
            <div class="sidebar-brand-icon me-3">
                <i class="fs-md" data-lucide="shield-check"></i>
            </div>
        @endif
        <div class="brand-text">
            <span class="brand-name fw-bold fs-md">{{ $companyName }}</span>
            <span class="brand-subtitle d-block fs-xs opacity-50">Accounting</span>
        </div>
    </div>

    <!-- Navigation Area -->
    <nav class="sidebar-nav flex-grow-1 overflow-y-auto overflow-x-hidden px-3 py-3">
        <ul class="nav flex-column gap-1">
            @foreach ($menus as $menu)
                @php
                    $allPages = collect();
                    foreach ($menu['groupings'] as $grouping) {
                        foreach ($grouping['pages'] as $page) {
                            $allPages->push($page);
                        }
                    }
                    $hasActiveChild = $allPages->contains('is_active', true);
                @endphp

                <li class="nav-item">
                    <!-- Parent Toggle Button -->
                    <button
                        class="sidebar-parent-link w-100 d-flex align-items-center justify-content-between rounded-5 {{ $hasActiveChild ? 'expanded' : 'collapsed' }} border-0 px-3 py-2"
                        data-bs-toggle="collapse"
                        data-bs-target="#menuGroup{{ $menu['id'] }}"
                        type="button"
                        aria-expanded="{{ $hasActiveChild ? 'true' : 'false' }}"
                    >
                        <div class="d-flex align-items-center text-truncate">
                            <span class="nav-icon-wrapper d-flex align-items-center justify-content-center me-2">
                                <i class="fs-md" data-lucide="{{ $menu['icon'] }}"></i>
                            </span>
                            <span class="nav-label fw-medium"> {{ __('folder_menu.' . $menu['name']) }} </span>
                        </div>

                        <i class="nav-arrow fs-sm opacity-50" data-lucide="chevron-right"></i>
                    </button>

                    <!-- Submenu List -->
                    <div
                        class="sidebar-collapse {{ $hasActiveChild ? 'show' : '' }} collapse"
                        id="menuGroup{{ $menu['id'] }}"
                        data-bs-parent=".sidebar-nav"
                    >
                        <ul class="nav flex-column sidebar-subnav mt-1 gap-1 ps-3">
                            @foreach ($menu['groupings'] as $grouping)
                                @if ($grouping['name'])
                                    <li class="nav-item">
                                        <span
                                            class="sidebar-subheader d-block text-uppercase opacity-50 mt-2 mb-1 px-3 py-1"
                                            style="font-size: 0.6rem; letter-spacing: 0.08em; font-weight: 600"
                                        >
                                            {{ __('page_menu.' . $grouping['name']) }}
                                        </span>
                                    </li>
                                @endif
                                @foreach ($grouping['pages'] as $page)
                                    <li class="nav-item">
                                        <a
                                            class="sidebar-sub-link d-flex align-items-center rounded-4 {{ $page['is_active'] ? 'active' : '' }} px-3 py-2"
                                            href="{{ $page['url'] }}"
                                        >
                                            <i
                                                class="sub-icon me-2 fs-sm"
                                                data-lucide="{{ $page['icon'] }}"
                                            ></i>
                                            <span class="fs-7 fw-normal">
                                                {{ __('page_menu.' . $page['name']) }}
                                            </span>
                                        </a>
                                    </li>
                                @endforeach
                            @endforeach
                        </ul>
                    </div>
                </li>
            @endforeach
        </ul>
    </nav>

    <!-- Footer / Logout -->
    <div class="sidebar-footer border-top border-sidebar p-3 mt-auto">
        <form autocomplete="off" action="{{ route('logout') }}" method="POST">
            @csrf
            <button
                class="sidebar-logout-btn w-100 d-flex align-items-center rounded-5 border-0 px-3 py-2"
                type="submit"
            >
                <i class="me-2 fs-sm opacity-50" data-lucide="log-out"></i>
                <span class="fs-7 fw-medium">{{ __('common.labels.logout') }}</span>
            </button>
        </form>
    </div>
</div>
