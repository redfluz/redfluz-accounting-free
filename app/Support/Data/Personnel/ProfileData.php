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
    public function __construct(
        #[MapName('name')]
        public ?string $name = null,

        #[MapName('locale')]
        public ?string $locale = null,

        #[MapName('theme')]
        public ?string $theme = null,

        #[MapName('picture')]
        public mixed $picture = null,

        #[MapName('current_password')]
        public ?string $currentPassword = null,

        #[MapName('password')]
        public ?string $password = null,

        #[MapName('password_confirmation')]
        public ?string $passwordConfirmation = null,
    ) {}

    /**
     * @return array<string, mixed>
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
