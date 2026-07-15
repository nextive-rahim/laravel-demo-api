<?php

namespace App\Http\Requests;

use App\Enums\CourseContentType;
use App\Models\CourseContent;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCourseContentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'type' => ['sometimes', 'required', Rule::enum(CourseContentType::class)],
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'is_active' => ['boolean'],
            'is_paid' => ['boolean'],
            'available_from' => ['nullable', 'date'],
            'position' => ['nullable', 'integer', 'min:0'],
            'payload' => ['nullable', 'array'],
            'course_section_id' => [
                'sometimes', 'nullable', 'integer',
                Rule::exists('course_sections', 'id')->where('course_id', $this->route('course')?->id),
            ],
        ];

        // Validate the `data` payload against the effective type: the new one if it
        // is being changed, otherwise the type already stored on the content item.
        $content = $this->route('content');
        $type = CourseContentType::tryFrom((string) $this->input('type'))
            ?? ($content instanceof CourseContent ? $content->type : null);

        if ($type !== null && $this->has('payload')) {
            foreach ($type->dataRules() as $field => $fieldRules) {
                $rules[$field] = $fieldRules;
            }
        }

        return $rules;
    }
}
