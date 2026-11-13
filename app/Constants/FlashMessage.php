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

namespace App\Constants;

use App\Enums\FileResources;

final class FlashMessage
{
    /**
     * Build a success message for a newly created resource.
     *
     * @param  FileResources  $resource  The affected resource.
     * @return string The formatted success message.
     */
    public static function created(FileResources $resource): string
    {
        return (string) __('flash.created', ['resource' => self::resourceName($resource)]);
    }

    /**
     * Build a success message for an updated resource.
     *
     * @param  FileResources  $resource  The affected resource.
     * @return string The formatted success message.
     */
    public static function updated(FileResources $resource): string
    {
        return (string) __('flash.updated', ['resource' => self::resourceName($resource)]);
    }

    /**
     * Build a success message for a deleted resource.
     *
     * @param  FileResources  $resource  The affected resource.
     * @return string The formatted success message.
     */
    public static function deleted(FileResources $resource): string
    {
        return (string) __('flash.deleted', ['resource' => self::resourceName($resource)]);
    }

    /**
     * Build a success message for an approved resource.
     *
     * @param  FileResources  $resource  The affected resource.
     * @return string The formatted success message.
     */
    public static function approved(FileResources $resource): string
    {
        return (string) __('flash.approved', ['resource' => self::resourceName($resource)]);
    }

    /**
     * Build a success message for a cancelled resource.
     *
     * @param  FileResources  $resource  The affected resource.
     * @return string The formatted success message.
     */
    public static function cancelled(FileResources $resource): string
    {
        return (string) __('flash.cancelled', ['resource' => self::resourceName($resource)]);
    }

    /**
     * Build a success message for a rejected resource.
     *
     * @param  FileResources  $resource  The affected resource.
     * @return string The formatted success message.
     */
    public static function rejected(FileResources $resource): string
    {
        return (string) __('flash.rejected', ['resource' => self::resourceName($resource)]);
    }

    /**
     * Get the translated resource name, falling back to the enum display name.
     *
     * @param  FileResources  $resource  The resource enum to resolve the display name for.
     * @return string The translated resource name or the enum display name as fallback.
     */
    private static function resourceName(FileResources $resource): string
    {
        $key = 'flash.resources.'.$resource->name;
        $translation = (string) __($key);

        return $translation === $key ? $resource->displayName() : $translation;
    }
}
