<?php

namespace App\Http\Requests;

use App\Enums\ResourceType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreFreeResourceRequest extends FormRequest
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
            'type' => ['required', new Enum(ResourceType::class)],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'thumbnail_path' => ['nullable', 'string', 'max:2048'],
            'file_url' => ['nullable', 'url', 'max:2048'],
            'is_published' => ['boolean'],
            'position' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
