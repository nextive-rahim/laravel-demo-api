<?php

namespace App\Http\Requests;

use App\Enums\ProgramCategory;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreProgramRequest extends FormRequest
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
            'category' => ['required', new Enum(ProgramCategory::class)],
            'title' => ['required', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'thumbnail_path' => ['nullable', 'string', 'max:2048'],
            'price' => ['nullable', 'integer', 'min:0'],
            'discount_price' => ['nullable', 'integer', 'min:0'],
            'is_published' => ['boolean'],
            'position' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
