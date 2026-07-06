<?php

namespace App\Enums;

enum ResourceType: string
{
    case Note = 'note';
    case Pdf = 'pdf';
    case Book = 'book';

    public function label(): string
    {
        return match ($this) {
            self::Note => 'Note',
            self::Pdf => 'PDF',
            self::Book => 'Book',
        };
    }
}
