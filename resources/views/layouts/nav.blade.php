@php use App\Models\System\Setting; @endphp
@inject('menuService', 'App\Services\MenuServices')

{{--
    Top navigation, built on the Bootstrap 6 Menu component.

    Each folder is one toggle button plus a sibling `.menu`. Inside a menu, a
    named grouping becomes a `.submenu` (a `.menu-item` trigger wrapping a
    nested `.menu`), which is how Bootstrap models a second level. Pages that
    belong to no grouping are emitted directly as `.menu-item`s so a folder
    without groupings does not get a pointless extra click.

    The markup contract is strict and Bootstrap relies on it:
      * the trigger must be `data-bs-toggle="menu"`
      * the `.menu` must be the trigger's NEXT SIBLING (no wrapper between)
      * a submenu trigger must wrap its nested `.menu` in `.submenu`
--}}

@php
    $menus = $menuService->getMenusForUser(auth()->user());
    $systemSetting = Setting::first();
    $companyName = $systemSetting->company_name ?? config('app.name', 'Redfluz');
    $logoPath = $systemSetting->logo_path ? asset('storage/' . $systemSetting->logo_path) : null;
@endphp

<nav class="topnav d-flex align-items-center flex-wrap gap-1" aria-label="Main">
    {{-- Brand ---------------------------------------------------------- --}}
    <a class="topnav-brand d-flex align-items-center gap-2 me-2 text-decoration-none" href="{{ url('/') }}">
        @if ($logoPath)
            <img class="topnav-logo" src="{{ $logoPath }}" alt="{{ $companyName }}" />
        @else
            <span class="topnav-brand-icon d-flex align-items-center justify-content-center">
                <i class="fs-md" data-lucide="shield-check"></i>
            </span>
        @endif
        <span class="topnav-brand-text d-none d-md-flex flex-column">
            <span class="brand-name fw-bold fs-7 lh-1">{{ $companyName }}</span>
            <span class="brand-subtitle fs-xs lh-1 opacity-50">Accounting</span>
        </span>
    </a>

    {{-- One menu per folder --------------------------------------------- --}}
    @foreach ($menus as $menu)
        @php
            $folderHasActive = collect($menu['groupings'])
                ->flatMap(fn ($grouping) => $grouping['pages'])
                ->contains('is_active', true);
        @endphp

        <div class="topnav-item">
            <button
                type="button"
                class="btn btn-ghost-secondary topnav-toggle d-flex align-items-center gap-2 rounded-4 px-3 py-2 {{ $folderHasActive ? 'active' : '' }}"
                data-bs-toggle="menu"
                data-bs-placement="bottom-start"
                data-bs-auto-close="outside"
                {{-- Bootstrap's default is submenuTrigger "both": hover opens a
                     grouping AND the click then toggles that same open submenu
                     shut again, so a real mouse click never leaves it visible
                     (verified against the docs' own example — it fails there
                     too on 6.0.0-alpha.1). "click" makes hover inert, so the
                     click is the only thing that opens a grouping. --}}
                data-bs-submenu-trigger="click"
                aria-expanded="false"
            >
                <i class="fs-md" data-lucide="{{ $menu['icon'] }}"></i>
                <span class="fw-medium">{{ __('folder_menu.' . $menu['name']) }}</span>
                <i class="fs-xs opacity-50" data-lucide="chevron-down"></i>
            </button>

            {{-- Deliberately NOT `.menu-scrollable`: it sets `overflow-y: auto`
                 on the folder menu, and that clips the submenus, which are
                 absolutely positioned OUTSIDE the menu's box. Long menus are
                 capped with a max-height instead, which scrolls without
                 clipping a flyout. --}}
            <div class="menu topnav-menu" aria-label="{{ __('folder_menu.' . $menu['name']) }}">
                @foreach ($menu['groupings'] as $grouping)
                    @if ($grouping['name'])
                        <div class="submenu">
                            <button
                                type="button"
                                class="menu-item rounded-4"
                            >
                                <span class="menu-item-label text-uppercase">
                                    {{ __('page_menu.' . $grouping['name']) }}
                                </span>
                                {{-- No chevron icon here: Bootstrap draws the
                                     submenu arrow itself via
                                     `.submenu > .menu-item:after`. Adding our
                                     own gave two arrows on every row. --}}
                            </button>

                            <div class="menu">
                                @foreach ($grouping['pages'] as $page)
                                    <a
                                        class="menu-item rounded-4 d-flex align-items-center gap-2 {{ $page['is_active'] ? 'active' : '' }}"
                                        href="{{ $page['url'] }}"
                                        @if ($page['is_active']) aria-current="page" @endif
                                    >
                                        <i class="fs-sm opacity-75 flex-shrink-0" data-lucide="{{ $page['icon'] }}"></i>
                                        <span class="text-truncate">{{ __('page_menu.' . $page['name']) }}</span>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @else
                        {{-- Ungrouped pages sit directly on the folder menu. --}}
                        @foreach ($grouping['pages'] as $page)
                            <a
                                class="menu-item rounded-4 d-flex align-items-center gap-2 {{ $page['is_active'] ? 'active' : '' }}"
                                href="{{ $page['url'] }}"
                                @if ($page['is_active']) aria-current="page" @endif
                            >
                                <i class="fs-sm opacity-75 flex-shrink-0" data-lucide="{{ $page['icon'] }}"></i>
                                <span class="text-truncate">{{ __('page_menu.' . $page['name']) }}</span>
                            </a>
                        @endforeach
                    @endif
                @endforeach
            </div>
        </div>
    @endforeach
</nav>
