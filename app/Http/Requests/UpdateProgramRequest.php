<?php

namespace App\Http\Requests;

use App\Enums\ProgramCategory;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateProgramRequest extends FormRequest
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
            'category' => ['sometimes', 'required', new Enum(ProgramCategory::class)],
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'thumbnail_path' => ['sometimes', 'nullable', 'string', 'max:2048'],
            'price' => ['nullable', 'integer', 'min:0'],
            'discount_price' => ['nullable', 'integer', 'min:0'],
            'is_published' => ['boolean'],
            'position' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
