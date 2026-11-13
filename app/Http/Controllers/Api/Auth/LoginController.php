<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Api\ApiController;
use App\Models\System\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class LoginController extends ApiController
{
    /**
     * Issue a Sanctum token for mobile app authentication.
     *
     * @param  Request  $request  The HTTP request containing email and password.
     * @return JsonResponse JSON response with the plain-text token and user data.
     *
     * @throws ValidationException
     */
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        $user = User::query()->where('email', $request->input('email'))->first();

        $password = $request->input('password');

        if ($user === null || ! Hash::check(is_string($password) ? $password : '', $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $rawDeviceName = $request->input('device_name');
        $deviceName = is_string($rawDeviceName) ? $rawDeviceName : 'mobile-app';
        $token = $user->createToken($deviceName)->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'picture' => $user->picture,
                'locale' => $user->locale,
            ],
        ]);
    }
}
