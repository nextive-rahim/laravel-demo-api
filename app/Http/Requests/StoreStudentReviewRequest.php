<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreStudentReviewRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'institute' => ['required', 'string', 'max:255'],
            'roll' => ['nullable', 'string', 'max:255'],
            'batch' => ['nullable', 'string', 'max:255'],
            'review' => ['nullable', 'string'],
            'image_path' => ['nullable', 'string', 'max:2048'],
            'video_url' => ['nullable', 'url', 'max:2048'],
            'is_published' => ['boolean'],
            'position' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
