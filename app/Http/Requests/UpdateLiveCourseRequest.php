<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateLiveCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'instructor_name' => ['nullable', 'string', 'max:255'],
            'thumbnail_path' => ['sometimes', 'nullable', 'string', 'max:2048'],
            'scheduled_at' => ['nullable', 'date'],
            'join_url' => ['nullable', 'url', 'max:2048'],
            'is_published' => ['boolean'],
        ];
    }
}
