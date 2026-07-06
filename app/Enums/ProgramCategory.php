<?php

namespace App\Enums;

enum ProgramCategory: string
{
    case Academic = 'academic';
    case Skills = 'skills';
    case Admission = 'admission';
    case Job = 'job';

    public function label(): string
    {
        return match ($this) {
            self::Academic => 'Academic',
            self::Skills => 'Skills',
            self::Admission => 'Admission',
            self::Job => 'Job Prep',
        };
    }
}
