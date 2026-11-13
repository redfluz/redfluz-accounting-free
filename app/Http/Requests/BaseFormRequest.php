<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

abstract class BaseFormRequest extends FormRequest
{
    protected function stringOrEmpty(string $field): string
    {
        $value = $this->validated($field);

        return is_string($value) ? $value : '';
    }

    protected function stringOrNull(string $field): ?string
    {
        $value = $this->validated($field);

        return is_string($value) ? $value : null;
    }

    protected function floatOrZero(string $field): float
    {
        $value = $this->validated($field);

        return is_numeric($value) ? (float) $value : 0.0;
    }

    protected function intOrZero(string $field): int
    {
        $value = $this->validated($field);

        return is_numeric($value) ? (int) $value : 0;
    }

    protected function nullableFloat(string $field): ?float
    {
        $value = $this->validated($field);

        if ($value === null) {
            return null;
        }

        return is_numeric($value) ? (float) $value : 0.0;
    }

    protected function nullableInt(string $field): ?int
    {
        $value = $this->validated($field);

        if ($value === null) {
            return null;
        }

        return is_numeric($value) ? (int) $value : 0;
    }

    protected function boolOrFalse(string $field): bool
    {
        $value = $this->validated($field);

        return is_bool($value) ? $value : false;
    }

    /**
     * @param  string  $field  The field name.
     * @return array<int|string, mixed>
     */
    protected function arrayOrEmpty(string $field): array
    {
        $value = $this->validated($field);

        return is_array($value) ? $value : [];
    }

    protected function inputStringOrEmpty(string $field): string
    {
        $value = $this->input($field);

        return is_string($value) ? $value : '';
    }

    protected function inputStringOrNull(string $field): ?string
    {
        $value = $this->input($field);

        return is_string($value) ? $value : null;
    }
}
