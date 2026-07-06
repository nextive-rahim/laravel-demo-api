<?php

namespace App\Enums;

enum AdPlacement: string
{
    case Banner = 'banner';
    case Popup = 'popup';
    case Home = 'home';

    /**
     * A human-friendly label for the placement.
     */
    public function label(): string
    {
        return match ($this) {
            self::Banner => 'Top banner (site-wide)',
            self::Popup => 'Popup (home)',
            self::Home => 'Home section',
        };
    }
}
