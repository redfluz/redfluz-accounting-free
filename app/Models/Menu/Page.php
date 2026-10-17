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
 *
 * All code, concepts, and intellectual property contained in this file are the
 * exclusive property of redfluz. Unauthorized copying, distribution, modification,
 * reverse engineering, or public display of this software via any medium is
 * strictly prohibited without explicit written permission from the copyright owner.
 */

namespace App\Models\Menu;

use App\Models\System\Role;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Models\Concerns\HasActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @mixin Builder<Page>
 *
 * @property string $id
 * @property string $folder_id
 * @property string|null $page_grouping_id
 * @property string $name
 * @property string $route_name
 * @property string $icon
 * @property CarbonInterface $created_at
 * @property CarbonInterface $updated_at
 *
 * @noinspection PhpUnused
 *
 * @property-read Folder|null $folder
 * @property-read PageGrouping|null $pageGrouping
 * @property-read Role|null $role
 * @property-read Collection<int, Activity> $activities
 * @property-read Collection<int, Activity> $activitiesAsCauser
 * @property-read Collection<int, Activity> $activitiesAsSubject
 */
#[Table('pages', key: 'id')]
#[Fillable([
    'folder_id',
    'page_grouping_id',
    'name',
    'route_name',
    'icon',
])]
class Page extends Model
{
    use HasActivity,HasUuids, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'id' => 'string',
            'folder_id' => 'string',
            'page_grouping_id' => 'string',
            'name' => 'string',
            'route_name' => 'string',
            'icon' => 'string',
        ];
    }

    /**
     * Define an inverse one-to-one or many relationship.
     * Get the role that the user belongs to.
     *
     * @return BelongsTo<Folder, $this>
     */
    public function folder(): BelongsTo
    {
        return $this->belongsTo(Folder::class);
    }

    /**
     * Define an inverse one-to-one or many relationship.
     * Get the page grouping that the page belongs to.
     *
     * @return BelongsTo<PageGrouping, $this>
     */
    public function pageGrouping(): BelongsTo
    {
        return $this->belongsTo(PageGrouping::class);
    }

    /**
     * Define an inverse one-to-one or many relationship.
     * Get the role that the user belongs to.
     *
     * @return BelongsTo<Role, $this>
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Get the options for logging activity using Spatie Activitylog.
     *
     * @return LogOptions Returns configured log options.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty();
    }
}
