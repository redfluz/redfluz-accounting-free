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

namespace App\Support\Traits;

use App\Enums\FileAction;
use App\Enums\FileResources;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Log;

/**
 * Whether the signed-in user may act on a file, and what happens when they may not.
 *
 * Shared by the controllers and by the form requests, because a request's
 * authorize() is now where a document says who may raise it. Both were answering
 * the same question, and two answers to one question is how a check ends up
 * applied to reads and forgotten on writes.
 *
 * The denial is a redirect with a message and a line in the log, not a bare 403.
 * That is deliberate and it is why this lives here rather than being a plain
 * boolean: a user who clicks something they may not do is told so on the page they
 * came from, and the attempt is recorded. A FormRequest returning false from
 * authorize() would give a 403 page with neither.
 */
trait ChecksFilePermission
{
    /**
     * Whether the signed-in user holds the permission for this file and action.
     *
     * @param  FileResources  $fileResources  The kind of file being acted on.
     * @param  FileAction  $fileAction  What is being done to it.
     * @return bool True when the user may, false when they may not or are not signed in.
     */
    public function hasAccess(FileResources $fileResources, FileAction $fileAction): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        return $user->hasFilePermission($fileResources, $fileAction);
    }

    /**
     * Refuse the action if the user may not perform it.
     *
     * Returns true when the user may, so that a form request can hand this
     * straight back from authorize(). It never returns false: a denial is raised,
     * not returned, because a caller that ignored the return value would otherwise
     * carry on into the action it was supposed to be stopped from.
     *
     * @param  FileResources  $resource  The kind of file being acted on.
     * @param  FileAction  $action  What is being done to it.
     * @return bool True, once the user is known to be permitted.
     *
     * @throws HttpResponseException When the user may not perform the action.
     */
    protected function allows(FileResources $resource, FileAction $action = FileAction::READ): bool
    {
        if (! $this->hasAccess($resource, $action)) {
            Log::channel('custom')->warning('Unauthorized access attempt by User ID: '.auth()->id());

            throw new HttpResponseException(
                redirect()->route('home')->with('error', 'Access denied.')
            );
        }

        return true;
    }
}
