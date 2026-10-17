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

use Spatie\LaravelData\DataCollection;

return [

    /*
    |--------------------------------------------------------------------------
    | Date formats
    |--------------------------------------------------------------------------
    |
    | The formats a date is allowed to arrive in, tried in order. Spatie's default
    | is ISO-8601 alone, which no form in this application sends: a date typed into
    | a form arrives as Y-m-d, and a timestamp read back out of the database is
    | either Y-m-d or Y-m-d H:i:s depending on the column. Listing the three means
    | Data::from($request->validated()) accepts a form as readily as it accepts a
    | model, instead of each DTO having to say which of them it expects.
    |
    | ISO-8601 is kept first so that a value already in that form is unchanged,
    | and because it is the only one of the three that can express a time.
    |
    */

    'date_format' => [
        'Y-m-d\TH:i:sP',
        'Y-m-d H:i:s',
        'Y-m-d',
    ],

    /*
    |--------------------------------------------------------------------------
    | Enum matching
    |--------------------------------------------------------------------------
    |
    | How an enum is matched when a string is given. By value, because that is
    | what a form field holds and what a database column holds. Matching by name
    | as well would mean a renamed case silently stopped being read.
    |
    */

    'enum_case_name_match_strategy' => 'value',

    /*
    |--------------------------------------------------------------------------
    | Collections
    |--------------------------------------------------------------------------
    */

    'collection_class' => DataCollection::class,

];
