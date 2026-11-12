<?php

namespace app\Enums\News;

enum NewsStatus: string
{
    case DRAFT = 'draft';
    case PUBLISHED = 'published';
    case ARCHIVED = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => __('news_status.draft'),
            self::PUBLISHED => __('news_status.published'),
            self::ARCHIVED => __('news_status.archived'),
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::DRAFT => 'bg-secondary-subtle text-secondary border border-secondary-subtle',
            self::PUBLISHED => 'bg-success-subtle text-success border border-success-subtle',
            self::ARCHIVED => 'bg-warning-subtle text-warning border border-warning-subtle',
        };
    }
}
