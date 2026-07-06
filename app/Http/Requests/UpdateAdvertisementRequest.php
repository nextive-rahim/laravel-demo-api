<?php

namespace App\Http\Requests;

use App\Enums\AdPlacement;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateAdvertisementRequest extends FormRequest
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
            'placement' => ['sometimes', 'required', new Enum(AdPlacement::class)],
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'image_path' => ['sometimes', 'nullable', 'string', 'max:2048'],
            'link_url' => ['nullable', 'url', 'max:2048'],
            'is_active' => ['boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'position' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
