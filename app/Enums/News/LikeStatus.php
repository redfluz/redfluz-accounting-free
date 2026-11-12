<?php

namespace app\Enums\News;

enum LikeStatus: string
{
    case LIKE = 'like';
    case UNLIKE = 'unlike';

    public function label(): string
    {
        return match ($this) {
            self::LIKE => __('like_status.like'),
            self::UNLIKE => __('like_status.unlike'),
        };
    }
}
