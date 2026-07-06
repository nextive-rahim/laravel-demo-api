<?php

namespace App\Enums;

enum PostType: string
{
    case Blog = 'blog';
    case News = 'news';

    /**
     * A human-friendly label for the type.
     */
    public function label(): string
    {
        return match ($this) {
            self::Blog => 'Blog',
            self::News => 'News',
        };
    }
}
