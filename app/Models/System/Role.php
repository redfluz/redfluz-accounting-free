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

namespace App\Models\System;

use App\Enums\DashboardRole;
use App\Models\Menu\Folder;
use App\Models\Menu\FolderPermission;
use App\Models\Menu\Page;
use App\Models\Menu\PageGroupingPermission;
use App\Models\Menu\PagePermission;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Models\Concerns\HasActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @mixin Builder<Role>
 *
 * @property string $id
 * @property string $name
 * @property DashboardRole $dashboard
 * @property CarbonInterface $created_at
 * @property CarbonInterface $updated_at
 * @property bool $is_default
 *
 * @noinspection PhpUnused
 *
 * @property-read Collection<int, User> $users
 * @property-read Collection<int, Folder> $menus
 * @property-read Collection<int, Page> $files
 * @property-read Collection<int, FolderPermission> $menuPermissions
 * @property-read Collection<int, PagePermission> $filePermissions
 * @property-read Collection<int, Activity> $activities
 * @property-read Collection<int, Activity> $activitiesAsCauser
 * @property-read Collection<int, Activity> $activitiesAsSubject
 */
#[Table('roles', key: 'id')]
#[Fillable(['name', 'dashboard', 'is_default'])]
class Role extends Model
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
            'name' => 'string',
            'dashboard' => DashboardRole::class,
            'is_default' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Boot the model and register event handlers.
     *
     * When a role is deleted, remove its permission rows so the three
     * permission matrices never carry orphaned references to a missing role.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::deleting(function (self $role): void {
            FolderPermission::query()->where('role_id', $role->id)->delete();
            PagePermission::query()->where('role_id', $role->id)->delete();
            PageGroupingPermission::query()->where('role_id', $role->id)->delete();
        });
    }

    /**
     * Define a one-to-many relationship.
     * Get the users that belong to the role.
     *
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Define a one-to-many relationship.
     * Get the folders that belong to the role as menu entries.
     *
     * @return HasMany<Folder, $this>
     */
    public function menus(): HasMany
    {
        return $this->hasMany(Folder::class);
    }

    /**
     * Define a one-to-many relationship.
     * Get the pages that belong to the role as file entries.
     *
     * @return HasMany<Page, $this>
     */
    public function files(): HasMany
    {
        return $this->hasMany(Page::class);
    }

    /**
     * Define a one-to-many relationship.
     * Get the folder permissions that belong to the role.
     *
     * @return HasMany<FolderPermission, $this>
     */
    public function menuPermissions(): HasMany
    {
        return $this->hasMany(FolderPermission::class);
    }

    /**
     * Define a one-to-many relationship.
     * Get the page permissions that belong to the role.
     *
     * @return HasMany<PagePermission, $this>
     */
    public function filePermissions(): HasMany
    {
        return $this->hasMany(PagePermission::class);
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
