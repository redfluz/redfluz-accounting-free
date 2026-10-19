@php
    use App\Models\System\Setting;
@endphp
@php
    $systemSetting = Setting::first();
    $companyName = $systemSetting->company_name ?? config('app.name', 'Redfluz');
    $logoPath = $systemSetting->logo_path ? asset('storage/'.$systemSetting->logo_path) : null;
    $money = fn (float $n) => $currencySymbol.number_format($n, 2);
@endphp

@extends('layouts.guest')

@section('content')
<div class="auth-page">
    <div class="auth-shell">

        {{-- ================= Left — Authentication ================= --}}
        <div class="auth-form-panel">
            <div class="auth-form-inner">

                <div class="auth-brand">
                    @if ($logoPath)
                        <img src="{{ $logoPath }}" alt="{{ $companyName }}" class="auth-logo" />
                    @else
                        <span class="auth-mark"><i data-lucide="zap"></i></span>
                        <span class="auth-brand-name">{{ $companyName }}</span>
                    @endif
                </div>

                <div class="auth-intro">
                    <h1 class="auth-heading">{{ __('common.labels.welcome_back') }}</h1>
                    <p class="auth-subheading">{{ __('common.labels.sign_in_account') }}</p>
                </div>

                @if (session('status') === 'password-changed')
                    <div class="alert theme-success auth-alert" data-testid="password-changed" role="alert">
                        {{ __('users.password_changed_relogin') }}
                    </div>
                @endif

                <form autocomplete="off" method="POST" action="{{ route('login') }}" class="auth-form" novalidate>
                    @csrf

                    <div class="auth-field">
                        <label class="form-label" for="email">
                            {{ __('common.labels.email_address') }}
                            <span class="auth-required" aria-hidden="true">*</span>
                        </label>
                        <label class="form-control auth-adorn">
                            <span class="auth-adorn-icon" aria-hidden="true"><i data-lucide="mail"></i></span>
                            <input
                                class="form-ghost @error('email') is-invalid @enderror"
                                id="email"
                                name="email"
                                type="email"
                                value="{{ old('email') }}"
                                placeholder="{{ __('common.labels.username_or_email') }}"
                                autocomplete="email"
                                autofocus
                                required
                            />
                        </label>
                        @error('email')
                            <div class="auth-error" role="alert">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="auth-field">
                        <label class="form-label" for="password">
                            {{ __('auth.password') }}
                            <span class="auth-required" aria-hidden="true">*</span>
                        </label>
                        <label class="form-control auth-adorn auth-adorn-end">
                            <span class="auth-adorn-icon" aria-hidden="true"><i data-lucide="lock"></i></span>
                            <input
                                class="form-ghost @error('password') is-invalid @enderror"
                                id="password"
                                name="password"
                                type="password"
                                placeholder="{{ __('auth.password') }}"
                                autocomplete="current-password"
                                required
                            />
                            <button class="auth-reveal" id="togglePassword" type="button"
                                aria-label="{{ __('common.actions.toggle_password_visibility') }}"
                                aria-pressed="false">
                                <i id="togglePasswordIcon" data-lucide="eye"></i>
                            </button>
                        </label>
                        @error('password')
                            <div class="auth-error" role="alert">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="auth-row">
                        <div class="form-field auth-check-field">
                            <input
                                class="check theme-primary"
                                id="remember"
                                name="remember"
                                type="checkbox"
                                {{ old('remember') ? 'checked' : '' }}
                            />
                            <div class="form-field-content">
                                <label for="remember">{{ __('auth.remember_me') }}</label>
                            </div>
                        </div>
                        @if (Route::has('password.request'))
                            <a class="auth-link" href="{{ route('password.request') }}">
                                {{ __('auth.forgot_password') }}
                            </a>
                        @endif
                    </div>

                    <button class="btn auth-submit" type="submit">
                        {{ __('auth.sign_in') }}
                    </button>
                </form>

                <p class="auth-footer">
                    &copy; {{ date('Y') }} {{ $companyName }}. {{ __('auth.all_rights_reserved') }}
                </p>
            </div>
        </div>

        {{-- ================= Right — Showcase ================= --}}
        <div class="auth-showcase">
            <div class="showcase-grid" aria-hidden="true"></div>
            <div class="showcase-glow showcase-glow-a" aria-hidden="true"></div>
            <div class="showcase-glow showcase-glow-b" aria-hidden="true"></div>

            <div class="showcase-content">

                {{-- Floating dashboard previews --}}
                <div class="showcase-canvas" aria-hidden="true">

                    <div class="sc-card sc-card-sales">
                        <div class="sc-card-head">
                            <span class="sc-title">{{ __('auth.showcase_sales') }}</span>
                            <span class="sc-pill">{{ __('auth.showcase_this_month') }}</span>
                        </div>
                        <div class="sc-card-body sc-donut-row">
                            <div class="sc-donut">
                                <svg viewBox="0 0 42 42" aria-hidden="true">
                                    <circle class="sc-donut-track" cx="21" cy="21" r="15.9" />
                                    <circle class="sc-donut-seg sc-seg-a" cx="21" cy="21" r="15.9" />
                                    <circle class="sc-donut-seg sc-seg-b" cx="21" cy="21" r="15.9" />
                                    <circle class="sc-donut-seg sc-seg-c" cx="21" cy="21" r="15.9" />
                                </svg>
                            </div>
                            <div class="sc-legend">
                                <span class="sc-legend-row"><i class="sc-dot sc-dot-a"></i>{{ __('auth.showcase_invoiced') }}<b>{{ $money(148920) }}</b></span>
                                <span class="sc-legend-row"><i class="sc-dot sc-dot-b"></i>{{ __('auth.showcase_paid') }}<b>{{ $money(112430) }}</b></span>
                                <span class="sc-legend-row"><i class="sc-dot sc-dot-c"></i>{{ __('auth.showcase_overdue') }}<b>{{ $money(36490) }}</b></span>
                            </div>
                        </div>
                        <div class="sc-stat">
                            <span>{{ __('auth.showcase_available') }}</span>
                            <b>{{ $money(112430) }}</b>
                        </div>
                    </div>

                    <div class="sc-card sc-card-receivables">
                        <div class="sc-card-head">
                            <span class="sc-title">{{ __('auth.showcase_receivables') }}</span>
                        </div>
                        <ul class="sc-list">
                            <li>
                                <span class="sc-list-name">INV-2041</span>
                                <span class="sc-list-bar"><i style="--pct:82%"></i></span>
                                <b class="sc-list-amt">{{ $money(18400) }}</b>
                            </li>
                            <li>
                                <span class="sc-list-name">INV-2039</span>
                                <span class="sc-list-bar"><i style="--pct:61%"></i></span>
                                <b class="sc-list-amt">{{ $money(12950) }}</b>
                            </li>
                            <li>
                                <span class="sc-list-name">INV-2034</span>
                                <span class="sc-list-bar"><i style="--pct:28%"></i></span>
                                <b class="sc-list-amt">{{ $money(5140) }}</b>
                            </li>
                        </ul>
                    </div>

                    <div class="sc-card sc-card-cash">
                        <div class="sc-card-head">
                            <span class="sc-title">{{ __('auth.showcase_cash') }}</span>
                            <span class="sc-delta">+12%</span>
                        </div>
                        <div class="sc-bars">
                            <i style="--h:38%"></i><i style="--h:56%"></i><i style="--h:44%"></i>
                            <i style="--h:72%"></i><i style="--h:61%"></i><i style="--h:88%"></i>
                            <i style="--h:100%"></i>
                        </div>
                        <div class="sc-stat">
                            <span>{{ __('auth.showcase_this_month') }}</span>
                            <b>{{ $money(96750) }}</b>
                        </div>
                    </div>

                    <div class="sc-card sc-card-journal">
                        <div class="sc-card-head">
                            <span class="sc-title">{{ __('auth.showcase_journal') }}</span>
                        </div>
                        <ul class="sc-rows">
                            <li><i class="sc-avatar"></i><span class="sc-row-main">Sales — INV-2041</span><b>{{ $money(4250) }}</b></li>
                            <li><i class="sc-avatar"></i><span class="sc-row-main">Purchase — PO-1188</span><b>{{ $money(1890) }}</b></li>
                            <li><i class="sc-avatar"></i><span class="sc-row-main">Bank — Receipt 0412</span><b>{{ $money(7300) }}</b></li>
                        </ul>
                    </div>
                </div>

                {{-- Brand + copy --}}
                <div class="showcase-copy">
                    @if ($logoPath)
                        <img src="{{ $logoPath }}" alt="" class="showcase-mark" />
                    @else
                        <span class="showcase-mark-fallback"><i data-lucide="zap"></i></span>
                    @endif

                    <h2 class="showcase-headline">{{ __('auth.showcase_headline') }}</h2>
                    <p class="showcase-body">{{ __('auth.showcase_body') }}</p>
                </div>

                <div class="showcase-dots" aria-hidden="true">
                    <i class="is-active"></i><i></i><i></i><i></i>
                </div>
            </div>
        </div>

    </div>
</div>

<style>
    /* =====================================================================
       AUTH — split shell, form left / showcase right
       ---------------------------------------------------------------------
       Bootstrap 6 contracts used here:
         - Responsive utilities take a PREFIX: `.lg\:flex`, not `.lg:d-flex`.
           (v5's infix form was silently inert, which is what collapsed the
           old panel to `display:none` at every width.)
         - `.form-select` is gone; `.form-control` now covers <select>.
         - Colour utilities are `.fg-*` / `.bg-*`. `.bg-2` and
           `.text-white-50` were removed in v6.
         - Theme tokens here are UNPREFIXED. This app compiles Bootstrap from
           its Sass source, which writes properties without a prefix and relies
           on a PostCSS step that is not configured in this project. Matching
           `var(--fg-2)` (not `var(--bs-fg-2)`) is what actually resolves.
         - Every token below is listed at the top of this block so the whole
           page can be re-tinted from one place.
       ===================================================================== */

    :root {
        /* These aliases follow the active Bootstrap palette. */
        --auth-deep: var(--primary-base);
        --auth-deep-2: var(--primary-bg-muted);
        --auth-accent: var(--primary-base);
        --auth-accent-soft: var(--primary-bg-subtle);
        --auth-ink: var(--fg-body);
        --auth-ink-soft: var(--fg-2);
    }

    html,
    body {
        height: 100%;
    }

    body:has(.auth-page) {
        background: #f6f4f4;
    }

    .auth-page {
        min-height: 100dvh;
        display: flex;
        align-items: stretch;
        padding: clamp(0.75rem, 2vw, 1.75rem);
    }

    /* Outer rounded shell holding the two halves. */
    .auth-shell {
        flex: 1 1 auto;
        display: grid;
        grid-template-columns: 1fr 1fr;
        min-height: calc(100dvh - clamp(1.5rem, 4vw, 3.5rem));
        background: var(--bg-body);
        border-radius: 26px;
        overflow: hidden;
        box-shadow: 0 1.5rem 3rem -1.25rem rgb(15 32 43 / 0.28);
    }

    /* ===================== Left — Authentication ===================== */
    .auth-form-panel {
        display: flex;
        align-items: center;
        padding: clamp(2rem, 4.5vw, 3.5rem);
        background: var(--bg-body);
    }

    .auth-form-inner {
        width: 100%;
        max-width: 25rem;
        margin-inline: auto;
    }

    .auth-brand {
        display: flex;
        align-items: center;
        gap: 0.55rem;
        margin-bottom: clamp(2rem, 5vh, 3rem);
    }

    .auth-logo {
        height: 28px;
        width: auto;
    }

    .auth-mark {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        border-radius: 9px;
        background: var(--auth-accent);
        color: #fff;
    }

    .auth-mark svg {
        width: 17px;
        height: 17px;
    }

    .auth-brand-name {
        font-size: 1.05rem;
        font-weight: 700;
        letter-spacing: -0.01em;
    }

    .auth-intro {
        margin-bottom: 1.75rem;
    }

    .auth-heading {
        margin: 0 0 0.35rem;
        font-size: clamp(1.5rem, 2.6vw, 1.85rem);
        font-weight: 700;
        letter-spacing: -0.02em;
    }

    .auth-subheading {
        margin: 0;
        color: var(--fg-2);
        font-size: 0.9rem;
    }

    .auth-alert {
        margin-bottom: 1.25rem;
    }

    .auth-form {
        display: flex;
        flex-direction: column;
        gap: 1.05rem;
    }

    .auth-field {
        display: flex;
        flex-direction: column;
        gap: 0.4rem;
    }

    .auth-field .form-label {
        margin: 0;
        font-size: 0.85rem;
        font-weight: 500;
        color: var(--fg-2);
    }

    .auth-required {
        color: var(--danger-base);
    }

    /* v6 form-adorn: the wrapper is a <label>, so clicking the icon or the
       padding focuses the field, and it raises the ring on :focus-within. */
    .auth-adorn {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        padding-inline: 0.85rem;
        min-height: 2.9rem;
        background: var(--bg-body);
    }

    .auth-adorn-icon {
        display: inline-flex;
        flex: 0 0 auto;
        color: var(--fg-3);
        transition: color 0.15s ease;
    }

    .auth-adorn-icon svg {
        width: 17px;
        height: 17px;
    }

    /* `.form-control:focus-visible, .form-control:focus-within` sets
       `outline: var(--focus-ring)` at specificity 0-2-0. Re-declaring the
       token inside a `:focus-within` rule matches that specificity exactly and
       wins on source order. (Setting `--focus-ring` on the static rule
       below is not enough — the focus rule re-resolves the shorthand against
       whatever the token holds at that moment.) */
    .auth-adorn:focus-within {
        border-color: var(--auth-accent);
        --focus-ring: 3px solid color-mix(in oklab, var(--auth-accent) 32%, transparent);
        --focus-ring-offset: -1px;
    }

    .auth-adorn:focus-within .auth-adorn-icon {
        color: var(--auth-accent);
    }

    .auth-adorn .form-ghost {
        flex: 1 1 auto;
        min-width: 0;
        border: 0;
        padding: 0;
        background: transparent;
        box-shadow: none;
        color: var(--fg-body);
    }

    .auth-adorn .form-ghost:focus {
        outline: none;
        box-shadow: none;
    }

    .auth-adorn .form-ghost::placeholder {
        color: var(--fg-3);
    }

    /* Server-side errors: colour the wrapper, since the input is borderless. */
    .auth-adorn:has(.is-invalid) {
        border-color: var(--danger-base);
    }

    .auth-adorn-end {
        padding-inline-end: 0.5rem;
    }

    .auth-reveal {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 auto;
        width: 2rem;
        height: 2rem;
        padding: 0;
        border: 0;
        border-radius: var(--radius-4);
        background: transparent;
        color: var(--fg-3);
        cursor: pointer;
        transition: color 0.15s ease, background-color 0.15s ease;
    }

    .auth-reveal:hover {
        color: var(--fg-1);
        background: var(--bg-2);
    }

    .auth-reveal svg {
        width: 17px;
        height: 17px;
    }

    .auth-error {
        font-size: 0.8rem;
        color: var(--danger-base);
    }

    .auth-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        flex-wrap: wrap;
    }

    /* v6 checkbox: `.check` lives on the input itself. */
    .auth-check-field {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        margin: 0;
    }

    .auth-check-field .check {
        /* Theme the mark to the accent rather than Bootstrap's blue. */
        --check-checked-bg: var(--auth-accent);
        --check-checked-border-color: var(--auth-accent);
    }

    .auth-check-field label {
        font-size: 0.875rem;
        color: var(--fg-2);
        cursor: pointer;
    }

    /* Keep the resting link readable; the selected palette supplies its accent. */
    .auth-link {
        font-size: 0.85rem;
        color: var(--auth-ink);
        text-decoration: underline;
        text-decoration-color: var(--primary-border);
    }

    .auth-link:hover {
        color: var(--primary-base);
        text-decoration-color: currentcolor;
    }

    .auth-submit {
        width: 100%;
        margin-top: 0.3rem;
        padding-block: 0.72rem;
        font-size: 0.95rem;
        font-weight: 600;
        color: var(--auth-ink);
        background: var(--primary-bg-subtle);
        border-color: var(--primary-border);
    }

    .auth-submit:hover,
    .auth-submit:focus-visible {
        background: var(--primary-bg-muted);
        border-color: var(--primary-base);
        color: var(--auth-ink);
    }

    .auth-footer {
        margin: clamp(1.75rem, 4vh, 2.5rem) 0 0;
        text-align: center;
        font-size: 0.78rem;
        color: var(--fg-3);
    }

    /* ===================== Right — Showcase ===================== */
    .auth-showcase {
        position: relative;
        overflow: hidden;
        isolation: isolate;
        display: flex;
        background: linear-gradient(155deg, var(--bg-body) 0%, var(--primary-bg-subtle) 45%, var(--primary-bg-muted) 100%);
    }

    /* Faint graph-paper texture */
    .showcase-grid {
        position: absolute;
        inset: 0;
        background-image:
            linear-gradient(rgb(238 105 105 / 0.07) 1px, transparent 1px),
            linear-gradient(90deg, rgb(238 105 105 / 0.07) 1px, transparent 1px);
        background-size: 46px 46px;
        mask-image: radial-gradient(120% 90% at 50% 40%, #000 55%, transparent 100%);
    }

    .showcase-glow {
        position: absolute;
        border-radius: 50%;
        filter: blur(70px);
        pointer-events: none;
    }

    .showcase-glow-a {
        top: -12%;
        left: -8%;
        width: 26rem;
        height: 26rem;
        background: rgb(253 170 170 / 0.30);
    }

    .showcase-glow-b {
        right: -12%;
        bottom: -14%;
        width: 30rem;
        height: 30rem;
        background: rgb(244 193 193 / 0.34);
    }

    .showcase-content {
        position: relative;
        z-index: 1;
        display: flex;
        flex-direction: column;
        justify-content: center;
        gap: clamp(1.5rem, 3.5vh, 2.5rem);
        width: 100%;
        padding: clamp(2rem, 4vw, 3.25rem);
    }

    /* --- Floating dashboard cards (the "image") ---
       A 2x2 grid with the right column nudged down, rather than absolutely
       positioned cards. Absolute positioning forces a bad trade-off: overlap
       the cards horizontally and the right-hand one loses its first characters
       (its content starts only ~0.9rem inside the card edge), or stagger them
       vertically and the lower card's header gets buried. A grid guarantees no
       overlap at any viewport, and the diagonal offset gives the same sense of
       depth that the overlap was buying. */
    .showcase-canvas {
        display: grid;
        grid-template-columns: 1.28fr 1fr;
        gap: 0.7rem 0.85rem;
        align-items: start;
    }

    .sc-card {
        padding: 0.8rem 0.9rem;
        border-radius: 14px;
        background: rgb(255 255 255 / 0.97);
        box-shadow: 0 1.1rem 2rem -0.7rem rgb(4 24 22 / 0.5);
        color: #10242a;
    }

    .sc-card-sales {
        grid-area: 1 / 1;
    }

    .sc-card-receivables {
        grid-area: 1 / 2;
        margin-top: 1.35rem;
    }

    .sc-card-cash {
        grid-area: 2 / 1;
    }

    .sc-card-journal {
        grid-area: 2 / 2;
        margin-top: 1.35rem;

        bottom: 4%;
        right: 0;
        width: 42%;
        z-index: 1;
    }

    .sc-card-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.5rem;
        margin-bottom: 0.6rem;
    }

    .sc-title {
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.01em;
    }

    .sc-pill {
        font-size: 0.58rem;
        font-weight: 600;
        color: #9f1239;
        background: #fee2e2;
        padding: 0.1rem 0.4rem;
        border-radius: 999px;
    }

    .sc-delta {
        font-size: 0.6rem;
        font-weight: 700;
        color: #047857;
    }

    .sc-donut-row {
        display: flex;
        align-items: center;
        gap: 0.7rem;
    }

    .sc-donut {
        flex: 0 0 auto;
        width: 62px;
    }

    .sc-donut svg {
        display: block;
        width: 100%;
        transform: rotate(-90deg);
    }

    .sc-donut circle {
        fill: none;
        stroke-width: 4.2;
    }

    .sc-donut-track {
        stroke: #e6edf0;
    }

    .sc-donut-seg {
        stroke-linecap: round;
    }

    .sc-seg-a { stroke: #9f1239; stroke-dasharray: 62 38; }
    .sc-seg-b { stroke: #f59e0b; stroke-dasharray: 24 76; stroke-dashoffset: -64; }
    .sc-seg-c { stroke: #ef4444; stroke-dasharray: 10 90; stroke-dashoffset: -90; }

    .sc-legend {
        display: flex;
        flex-direction: column;
        gap: 0.2rem;
        min-width: 0;
        flex: 1 1 auto;
    }

    .sc-legend-row {
        display: flex;
        align-items: center;
        gap: 0.3rem;
        font-size: 0.56rem;
        color: #55707a;
    }

    .sc-legend-row b {
        margin-left: auto;
        font-size: 0.58rem;
        color: #10242a;
    }

    .sc-dot {
        flex: 0 0 auto;
        width: 6px;
        height: 6px;
        border-radius: 50%;
    }

    .sc-dot-a { background: #9f1239; }
    .sc-dot-b { background: #f59e0b; }
    .sc-dot-c { background: #ef4444; }

    .sc-stat {
        display: flex;
        align-items: baseline;
        justify-content: space-between;
        gap: 0.5rem;
        margin-top: 0.6rem;
        padding-top: 0.5rem;
        border-top: 1px solid #edf2f4;
        font-size: 0.58rem;
        color: #55707a;
    }

    .sc-stat b {
        font-size: 0.7rem;
        color: #10242a;
    }

    .sc-list,
    .sc-rows {
        display: flex;
        flex-direction: column;
        gap: 0.4rem;
        margin: 0;
        padding: 0;
        list-style: none;
    }

    .sc-list li {
        display: grid;
        grid-template-columns: auto 1fr auto;
        align-items: center;
        gap: 0.4rem;
    }

    .sc-list-name {
        font-size: 0.56rem;
        font-weight: 600;
        color: #55707a;
    }

    .sc-list-bar {
        height: 4px;
        border-radius: 999px;
        background: #e6edf0;
        overflow: hidden;
    }

    .sc-list-bar i {
        display: block;
        height: 100%;
        width: var(--pct);
        border-radius: 999px;
        background: linear-gradient(90deg, #9f1239, #fb7185);
    }

    .sc-list-amt {
        font-size: 0.58rem;
        color: #10242a;
    }

    .sc-bars {
        display: flex;
        align-items: flex-end;
        gap: 5px;
        height: 56px;
    }

    .sc-bars i {
        flex: 1 1 0;
        height: var(--h);
        border-radius: 3px 3px 1px 1px;
        background: linear-gradient(180deg, #fb7185, #9f1239);
        opacity: 0.9;
    }

    .sc-rows li {
        display: flex;
        align-items: center;
        gap: 0.4rem;
        font-size: 0.56rem;
        color: #55707a;
    }

    .sc-avatar {
        flex: 0 0 auto;
        width: 14px;
        height: 14px;
        border-radius: 50%;
        background: linear-gradient(135deg, #fecdd3, #9f1239);
    }

    .sc-row-main {
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .sc-rows b {
        margin-left: auto;
        color: #10242a;
        font-size: 0.58rem;
    }

    /* --- Copy block --- */
    .showcase-copy {
        text-align: center;
        color: var(--auth-ink);
    }

    .showcase-mark {
        height: 56px;
        width: auto;
        margin-bottom: 1.1rem;
    }

    .showcase-mark-fallback {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 56px;
        height: 56px;
        margin-bottom: 1.1rem;
        border-radius: 16px;
        background: rgb(255 255 255 / 0.14);
        border: 1px solid var(--primary-border);
        color: var(--fg-body);
    }

    .showcase-mark-fallback svg {
        width: 26px;
        height: 26px;
    }

    .showcase-headline {
        margin: 0 auto 0.7rem;
        max-width: 22ch;
        font-size: clamp(1.35rem, 2.5vw, 1.9rem);
        font-weight: 700;
        line-height: 1.25;
        letter-spacing: -0.02em;
        text-wrap: balance;
    }

    .showcase-body {
        margin: 0 auto;
        max-width: 42ch;
        font-size: 0.85rem;
        line-height: 1.6;
        color: var(--auth-ink-soft);
    }

    .showcase-dots {
        display: flex;
        justify-content: center;
        gap: 0.4rem;
    }

    .showcase-dots i {
        width: 34px;
        height: 4px;
        border-radius: 999px;
        background: var(--primary-border);
    }

    .showcase-dots i.is-active {
        background: var(--primary-base);
    }

    /* ===================== Responsive ===================== */
    @media (max-width: 991.98px) {
        .auth-shell {
            grid-template-columns: 1fr;
            min-height: 0;
        }

        .auth-form-panel {
            padding: clamp(1.75rem, 6vw, 3rem);
        }

        .auth-showcase {
            display: none;
        }
    }

    @media (max-width: 575.98px) {
        .auth-page {
            padding: 0;
        }

        .auth-shell {
            border-radius: 0;
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .auth-showcase * {
            transition: none !important;
        }
    }
</style>
@endsection

@section('script')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const toggle = document.getElementById('togglePassword');
            const password = document.getElementById('password');

            if (!toggle || !password) return;

            const renderIcon = function (name) {
                const current = document.getElementById('togglePasswordIcon');
                if (!current) return;

                const icon = document.createElement('i');
                icon.id = 'togglePasswordIcon';
                icon.dataset.lucide = name;
                current.replaceWith(icon);

                if (window.lucide && typeof window.lucide.createIcons === 'function') {
                    window.lucide.createIcons();
                }
            };

            toggle.addEventListener('click', function () {
                const isHidden = password.getAttribute('type') === 'password';
                password.setAttribute('type', isHidden ? 'text' : 'password');
                toggle.setAttribute('aria-pressed', String(isHidden));
                renderIcon(isHidden ? 'eye-off' : 'eye');
            });
        });
    </script>
@endsection
