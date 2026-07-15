<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCourseRequest extends FormRequest
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
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'overview' => ['nullable', 'string', 'max:20000'],
            'thumbnail_path' => ['sometimes', 'nullable', 'string', 'max:2048'],
            'instructor_name' => ['nullable', 'string', 'max:255'],
            'instructor_title' => ['nullable', 'string', 'max:255'],
            'instructor_image_path' => ['sometimes', 'nullable', 'string', 'max:2048'],
            'price' => ['nullable', 'integer', 'min:0'],
            'discount_price' => ['nullable', 'integer', 'min:0'],
            'rating' => ['nullable', 'numeric', 'min:0', 'max:5'],
            'rating_count' => ['nullable', 'integer', 'min:0'],
            'is_published' => ['boolean'],
            'is_featured' => ['boolean'],
            'instructor_ids' => ['sometimes', 'array'],
            'instructor_ids.*' => ['integer', 'exists:instructors,id'],
        ];
    }
}
