<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCourseSectionRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255'],
            'is_active' => ['boolean'],
            'position' => ['nullable', 'integer', 'min:0'],
            // A sub-section's parent must be a top-level section of the same course.
            'parent_id' => [
                'nullable', 'integer',
                Rule::exists('course_sections', 'id')
                    ->where('course_id', $this->route('course')?->id)
                    ->whereNull('parent_id'),
            ],
        ];
    }
}
