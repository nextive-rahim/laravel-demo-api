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
            // Exams have no external link; questions are attached from the MCQ store
            // and total marks are computed from those questions.
            self::Exam => [
                'payload.duration_minutes' => ['nullable', 'integer', 'min:1'],
                'payload.pass_mark' => ['nullable', 'integer', 'min:0', 'max:100'],
                'payload.start_time' => ['nullable', 'date'],
                'payload.end_time' => ['nullable', 'date', 'after_or_equal:payload.start_time'],
                'payload.result_publish_time' => ['nullable', 'date', 'after_or_equal:payload.end_time'],
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
