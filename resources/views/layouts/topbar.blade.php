@php
    use App\Models\System\Setting;

    $systemSetting = Setting::first();
@endphp

<div class="topbar-container sticky-top px-3 py-2 lg:px-4">
    <div class="d-flex align-items-center justify-content-between gap-3 w-100">
        <!-- Left: main navigation -->
        <div class="flex-grow-1 min-width-0">
            @auth
                @include('layouts.nav')
            @endauth
        </div>

        <!-- Right: Actions & Profile -->
        <div class="d-flex align-items-center gap-2 flex-shrink-0">
            @guest
                <a class="btn btn-sm btn-solid theme-primary fw-medium rounded-4 px-3" href="{{ route('login') }}">
                    {{ __('common.labels.login') }}
                </a>
            @else
                <div class="vr mx-2 opacity-25 d-none sm:d-block" style="height: 24px"></div>

                <!-- User Profile Menu (Bootstrap 6 Menu component) -->
                <div class="position-relative">
                    <button
                        class="btn btn-ghost-secondary d-flex align-items-center rounded-pill gap-2 border-0 p-1 pe-2"
                        id="userProfileMenu"
                        data-bs-toggle="menu"
                        data-bs-auto-close="outside"
                        type="button"
                        aria-expanded="false"
                    >
                        @php $avatar = Auth::user()->picture ?? Auth::user()->avatar ?? Auth::user()->avatar_path ?? Auth::user()->profile_photo_path; @endphp
                        @if ($avatar)
                            <img
                                class="rounded-circle object-fit-cover"
                                src="{{ str_starts_with($avatar, 'http') ? $avatar : asset('storage/' . $avatar) }}"
                                alt="{{ Auth::user()->name }}"
                                style="width: 32px; height: 32px"
                            />
                        @else
                            <div
                                class="avatar-circle bg-primary rounded-circle d-flex align-items-center justify-content-center fw-bold fs-7"
                                style="width: 32px; height: 32px"
                            >
                                {{ strtoupper(substr(Auth::user()->name ?? 'A', 0, 1)) }}
                            </div>
                        @endif

                        <span
                            class="fw-semibold fs-7 fg-body text-truncate d-none sm:d-inline"
                            style="max-width: 130px"
                        >
                            {{ Auth::user()->name ?? 'Account' }}
                        </span>

                        <i class="fs-xs fg-secondary ms-1" data-lucide="chevron-down"></i>
                    </button>

                    <div
                        class="menu menu-end border-secondary rounded-5 mt-2 border border-10 p-1 shadow-lg"
                        aria-labelledby="userProfileMenu"
                        style="min-width: 220px"
                    >
                        <div class="border-bottom border-secondary mb-1 border-10 px-3 py-2">
                            <p class="fw-bold fs-7 text-truncate mb-0">{{ Auth::user()->name }}</p>
                            <p class="fs-xs fg-secondary text-truncate mb-0">{{ Auth::user()->email }}</p>
                        </div>

                        <a
                            class="menu-item rounded-4 fs-7 d-flex align-items-center px-3 py-2"
                            href="{{ route('personnel.profile.edit') }}"
                        >
                            <i class="fg-secondary me-2.5 opacity-75" data-lucide="contact"></i>
                            {{ __('common.labels.profile') }}
                        </a>

                        <div class="menu-divider border-secondary my-1 border-10"></div>

                        <form autocomplete="off" action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button
                                class="menu-item rounded-4 fs-7 fg-danger d-flex align-items-center px-3 py-2"
                                type="submit"
                            >
                                <i class="me-2.5 opacity-75" data-lucide="log-out"></i>
                                {{ __('common.labels.logout') }}
                            </button>
                        </form>
                    </div>
                </div>
            @endguest
        </div>
    </div>
</div>
