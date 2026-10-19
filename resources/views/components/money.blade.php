{{--
    Renders a financial figure with an explicit + / - sign.

    Why this exists
    ---------------
    Gain/loss colour is reserved for row backgrounds only. Financial text stays in
    the default foreground so the digits are easy to read, which means colour
    can no longer be the thing that tells you whether a number is positive.

    So the direction has to survive as something other than colour:

      - a signed number is readable by everyone, including colour-blind users
        and anyone printing the page in greyscale;
      - it survives copy-paste into Excel, unlike an arrow icon;
      - it is already present on printed financial reports, so it matches what
        a clerk sees when they print the same statement.

    This is WCAG 1.4.1 (Use of Color): colour must never be the only visual
    means of conveying information.

    Usage
        <x-money :value="$netProfit" />
        <x-money :value="$ledger->running_balance" :decimals="2" />
        <x-money :value="$row['amount']" :signed="false" />
--}}
@props([
    'value' => 0,
    'decimals' => 2,
    'prefix' => null,
    /** Show a leading + on positive values. Negative always gets a sign. */
    'signed' => true,
    /** Printed instead of 0.00 when the value is exactly zero. */
    'zero' => null,
])

@php
    // Default prefix is the system currency symbol (shared globally from
    // AppServiceProvider). Pass an empty string to suppress it.
    if ($prefix === null) {
        $prefix = $currencySymbol . ' ';
    }

    $number = (float) $value;

    // "Zero" means zero *as displayed*. A raw epsilon check would let a value
    // like -0.001 through the sign logic and render "-0.00", which reads like a
    // bug on a statement. Compare the rounded value instead.
    $isZero = (float) number_format($number, $decimals, '.', '') === 0.0;

    if ($isZero && $zero !== null) {
        echo e($prefix.$zero);

        return;
    }

    // ASCII hyphen, not U+2212 MINUS SIGN: Excel and most accounting imports
    // will not parse the typographic minus when the value is pasted.
    $sign = '';

    if (! $isZero) {
        $sign = $number > 0 && $signed ? '+' : '-';
    }

    echo e($prefix.$sign.number_format(abs($number), $decimals));
@endphp
