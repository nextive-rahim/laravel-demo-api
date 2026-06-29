<?php

namespace App\Http\Requests;

use App\Enums\CourseContentType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCourseContentRequest extends FormRequest
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
            'type' => ['required', Rule::enum(CourseContentType::class)],
            'title' => ['required', 'string', 'max:255'],
            'position' => ['nullable', 'integer', 'min:0'],
            'payload' => ['nullable', 'array'],
        ];

        $type = CourseContentType::tryFrom((string) $this->input('type'));

        if ($type !== null) {
            $rules = array_merge($rules, $type->dataRules());
        }

        return $rules;
    }
}
