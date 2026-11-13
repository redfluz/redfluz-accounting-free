<?php

/**
 * ============================================================================
 *
 * @author      redfluz
 *
 * @github      https://github.com/redfluz
 *
 * @youtube     https://www.youtube.com/@redfluz
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

declare(strict_types=1);

namespace App\Http\Requests\Bank\Receipt;

use App\Http\Requests\BaseFormRequest;
use App\Support\Traits\ChecksFilePermission;

/**
 * The selection of refunds a batch action applies to.
 *
 * Approving and cancelling are two different acts on the same selection, so both
 * ask for the same thing and only that. They each state the rules themselves
 * rather than sharing them, because a batch action that gained a field of its own
 * would then have to be declared in two places to be honoured.
 *
 * The numbers are returned as a list of strings rather than as the raw input,
 * because every caller puts them straight into a query and the type of each entry
 * is the difference between a query that finds the document and one that does
 * not.
 */
abstract class BatchReceiptRequest extends BaseFormRequest
{
    use ChecksFilePermission;

    /**
     * Permission is checked by the controller's allows() call, which distinguishes
     * approving from cancelling.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'document_number' => 'required|array|min:1',
            'document_number.*' => 'required|string',
        ];
    }

    /**
     * The refund numbers the user selected, in the order they were sent.
     *
     * Entries that are not strings are dropped rather than cast. A number that
     * arrived as something else has failed the rules already, and casting it here
     * would hide that from the caller that reports which document stopped a batch.
     *
     * @return list<string>
     */
    public function documentNumbers(): array
    {
        $numbers = $this->validated('document_number');

        if (! is_array($numbers)) {
            return [];
        }

        return array_values(array_filter(
            $numbers,
            static fn (mixed $number): bool => is_string($number) && $number !== '',
        ));
    }
}
