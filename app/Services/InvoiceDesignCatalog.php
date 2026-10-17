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
 */

namespace App\Services;

use InvalidArgumentException;

/** Provides the invoice layout samples stored in app/Config/finance.php. */
class InvoiceDesignCatalog
{
    /** @return array<string, array<string, string>> */
    public function layouts(): array
    {
        /** @var array{invoice_designs: array<string, array<string, string>>} $catalog */
        $catalog = require app_path('Config/finance.php');

        return $catalog['invoice_designs'];
    }

    /**
     * @param  string  $key  The layout identifier key.
     * @return array<string, string>
     */
    public function layout(string $key): array
    {
        $layouts = $this->layouts();

        if (! array_key_exists($key, $layouts)) {
            throw new InvalidArgumentException("Unknown invoice design layout: $key");
        }

        return $layouts[$key];
    }
}
