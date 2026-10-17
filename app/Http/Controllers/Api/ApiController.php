<?php

namespace App\Http\Controllers\Api;

use App\Enums\FileAction;
use App\Enums\FileResources;
use App\Http\Controllers\Controller;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class ApiController extends Controller
{
    /**
     * Refuse the action if the user may not perform it.
     *
     * An API has no page to redirect back to and no session message to show, so a
     * denial is a 403 here rather than the redirect the web controller uses.
     *
     * @param  FileResources  $resource  The kind of file being acted on.
     * @param  FileAction  $action  What is being done to it.
     * @return bool True, once the user is known to be permitted.
     *
     * @throws AccessDeniedHttpException When the user may not perform the action.
     */
    protected function allows(FileResources $resource, FileAction $action = FileAction::READ): bool
    {
        if (! $this->hasAccess($resource, $action)) {
            throw new AccessDeniedHttpException('Unauthorized action.');
        }

        return true;
    }
}
