<?php

namespace App\Http\Requests;

use App\Enums\PostType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdatePostRequest extends FormRequest
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
            'type' => ['sometimes', 'required', new Enum(PostType::class)],
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'body' => ['sometimes', 'required', 'string'],
            'image_path' => ['sometimes', 'nullable', 'string', 'max:2048'],
            'is_published' => ['boolean'],
        ];
    }
}
