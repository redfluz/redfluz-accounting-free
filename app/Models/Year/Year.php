<?php

declare(strict_types=1);

namespace App\Models\Year;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * One financial year.
 *
 * A year carries the dates the accounting year runs between and whether it has
 * been closed. Periods belong to it, so closing the year closes what it holds.
 *
 * @mixin Builder<Year>
 *
 * @property string $id
 * @property string $name
 * @property int $year
 * @property CarbonInterface $start_date
 * @property CarbonInterface $end_date
 * @property bool $is_closed
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property CarbonInterface|null $deleted_at
 * @property-read Collection<int, Period> $periods
 */
#[Table('years', key: 'id')]
#[Fillable([
    'name',
    'year',
    'start_date',
    'end_date',
    'is_closed',
])]
class Year extends Model
{
    use HasUuids, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'start_date' => 'date',
            'end_date' => 'date',
            'is_closed' => 'boolean',
        ];
    }

    /**
     * Define a one-to-many relationship.
     * Get the periods that belong to this financial year.
     *
     * @return HasMany<Period, $this>
     */
    public function periods(): HasMany
    {
        return $this->hasMany(Period::class);
    }
}
