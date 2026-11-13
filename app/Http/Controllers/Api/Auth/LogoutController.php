<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Api\ApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LogoutController extends ApiController
{
    /**
     * Revoke the current user's access token.
     *
     * @param  Request  $request  The authenticated HTTP request.
     * @return JsonResponse JSON response confirming logout.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json(['message' => 'Logged out successfully.']);
    }
}
