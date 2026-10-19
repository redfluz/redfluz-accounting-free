<?php

namespace App\Services\Personnel;

use App\Models\System\User;
use App\Support\Traits\StoresUploads;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class ProfileService
{
    use StoresUploads;

    /**
     * Update the user's profile information.
     *
     * @param  User  $user  The user to update.
     * @param  string  $name  The new name.
     * @param  string  $locale  The new locale.
     * @param  string  $theme  The selected interface theme.
     * @param  UploadedFile|null  $picture  The optional profile picture.
     * @param  string|null  $password  The optional new password.
     * @return bool Whether the user must re-authenticate (forced password change was completed).
     */
    public function updateProfile(
        User $user,
        string $name,
        string $locale,
        string $theme,
        ?UploadedFile $picture = null,
        ?string $password = null,
    ): bool {
        $mustReauthenticate = $user->must_change_password;

        if ($picture !== null) {
            // storeUpload() throws RuntimeException when the write fails.
            $picturePath = $this->storeUpload($picture, 'profiles', self::PUBLIC_DISK);

            if ($user->picture && Storage::disk('public')->exists($user->picture)) {
                Storage::disk('public')->delete($user->picture);
            }

            $user->picture = $picturePath;
        }

        $user->name = $name;
        $user->locale = $locale;
        $user->theme = $theme;

        if (! empty($password)) {
            $user->password = Hash::make($password);
            $user->must_change_password = false;
        }

        $user->save();

        return $mustReauthenticate;
    }

    /**
     * Log out the user and invalidate the session after a forced password change.
     *
     * @param  Request  $request  The HTTP request instance.
     * @return void Returns nothing after logging out and invalidating the session.
     */
    public function logoutAfterPasswordChange(Request $request): void
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}
