<?php

/**
 * ============================================================================
 *
 * @author      redfluz
 *
 * @github      https://github.com/redfluz
 *
 * @youtube     https://github.com/redfluz
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

namespace App\Support\Data\Personnel;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;

class ProfileData extends Data
{
    /**
     * Carry validated profile fields to the profile update action.
     *
     * @param  string|null  $name  The user's display name.
     * @param  string|null  $locale  The preferred interface language.
     * @param  string|null  $theme  The selected interface theme.
     * @param  mixed  $picture  The optional uploaded profile picture.
     * @param  mixed  $backgroundImage  The optional uploaded background image.
     * @param  bool|null  $removeBackground  Whether to remove the existing background.
     * @param  string|null  $currentPassword  The current password for password changes.
     * @param  string|null  $password  The optional new password.
     * @param  string|null  $passwordConfirmation  The repeated new password.
     * @return void Initializes the profile data fields.
     */
    public function __construct(
        #[MapName('name')]
        public ?string $name = null,

        #[MapName('locale')]
        public ?string $locale = null,

        #[MapName('theme')]
        public ?string $theme = null,

        #[MapName('picture')]
        public mixed $picture = null,

        #[MapName('background_image')]
        public mixed $backgroundImage = null,

        #[MapName('remove_background')]
        public ?bool $removeBackground = null,

        #[MapName('current_password')]
        public ?string $currentPassword = null,

        #[MapName('password')]
        public ?string $password = null,

        #[MapName('password_confirmation')]
        public ?string $passwordConfirmation = null,
    ) {}

    /**
     * Return the profile fields that do not require upload or password handling.
     *
     * @return array<string, mixed> The name, language, and theme values.
     */
    public function toRow(): array
    {
        return [
            'name' => $this->name,
            'locale' => $this->locale,
            'theme' => $this->theme,
        ];
    }
}
