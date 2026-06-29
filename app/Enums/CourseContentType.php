<?php

namespace App\Enums;

enum CourseContentType: string
{
    case Note = 'note';
    case Pdf = 'pdf';
    case Exam = 'exam';
    case Video = 'video';
    case Live = 'live';
    case Link = 'link';

    /**
     * The validation rules for the type-specific `data` payload.
     *
     * @return array<string, array<int, string>>
     */
    public function dataRules(): array
    {
        return match ($this) {
            self::Note => [
                'payload.body' => ['required', 'string'],
            ],
            self::Pdf => [
                'payload.url' => ['required', 'url', 'max:2048'],
            ],
            self::Exam => [
                'payload.url' => ['nullable', 'url', 'max:2048'],
                'payload.duration_minutes' => ['nullable', 'integer', 'min:1'],
                'payload.total_marks' => ['nullable', 'integer', 'min:1'],
            ],
            self::Video => [
                'payload.url' => ['required', 'url', 'max:2048'],
                'payload.provider' => ['nullable', 'string', 'max:50'],
            ],
            self::Live => [
                'payload.url' => ['required', 'url', 'max:2048'],
                'payload.scheduled_at' => ['required', 'date'],
            ],
            self::Link => [
                'payload.url' => ['required', 'url', 'max:2048'],
            ],
        };
    }
}
