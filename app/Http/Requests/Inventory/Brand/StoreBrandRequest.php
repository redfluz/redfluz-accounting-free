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

namespace App\Http\Requests\Inventory\Brand;

use App\Enums\FileAction;
use App\Enums\FileResources;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * The fields a brand is created from.
 *
 * The rules live here rather than in the controller so that the controller can be
 * handed a request that has already been checked. A rule stated once cannot be
 * stated twice in two places that then disagree.
 *
 * The name carries a case-insensitive duplicate check, because the database index
 * is case-insensitive and a plain case-sensitive rule would let "asus" past
 * validation only to fail as a raw constraint violation.
 */
class StoreBrandRequest extends BrandRequest
{
    /**
     * Who may create a brand.
     *
     * The permission lives here rather than in the controller, so that the request
     * says everything about who may send it and what it must contain. Laravel
     * asks this before the controller runs, so a request that is not permitted
     * never reaches the action it was meant to guard.
     *
     * It refuses by redirecting with an explanation and writing the attempt to
     * the log, which is what this check has always done and what returning a
     * plain false would lose.
     */
    public function authorize(): bool
    {
        return $this->allows(FileResources::BRANDS, FileAction::ADD);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('brands', 'name'),
                // The database index is case-insensitive, so mirror it here and
                // report a validation error rather than a constraint violation.
                fn (string $attribute, mixed $value, Closure $fail) => $this->rejectDuplicateBrandName($value),
            ],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Reject a brand name that duplicates an existing brand regardless of case.
     *
     * The brands table enforces uniqueness with a case-insensitive index, so a
     * plain case-sensitive check would let "asus" pass validation and then fail
     * as a raw constraint violation.
     */
    private function rejectDuplicateBrandName(mixed $value): void
    {
        if (! is_string($value)) {
            return;
        }

        $exists = DB::table('brands')
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($value)])
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'name' => __('brands.validation.name_already_exists'),
            ]);
        }
    }
}
