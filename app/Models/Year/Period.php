<?php

declare(strict_types=1);

namespace App\Models\Year;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * One accounting period inside a financial year.
 *
 * A period names the month it covers and whether it has been closed. Posting is
 * refused into a closed period, and strict journal posting also refuses any
 * period other than the one that contains today.
 *
 * @mixin Builder<Period>
 *
 * @property string $id
 * @property string $year_id
 * @property int $period
 * @property string $name
 * @property CarbonInterface $start_date
 * @property CarbonInterface $end_date
 * @property bool $is_closed
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property CarbonInterface|null $deleted_at
 * @property-read Year $year
 */
#[Table('periods', key: 'id')]
#[Fillable([
    'year_id', 'period', 'name', 'start_date', 'end_date', 'is_closed',
])]
class Period extends Model
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
            'period' => 'integer',
            'start_date' => 'date',
            'end_date' => 'date',
            'is_closed' => 'boolean',
        ];
    }

    /**
     * Define an inverse one-to-one or many relationship.
     * Get the financial year this period belongs to.
     *
     * @return BelongsTo<Year, $this>
     */
    public function year(): BelongsTo
    {
        return $this->belongsTo(Year::class);
    }
}
