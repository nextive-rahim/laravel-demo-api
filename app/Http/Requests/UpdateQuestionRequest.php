<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class UpdateQuestionRequest extends FormRequest
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
     * When `options` are supplied the full set is replaced, so it is validated
     * with the same rules as creation.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'subcategory_id' => ['sometimes', 'required', 'integer', 'exists:subcategories,id'],
            'body' => ['sometimes', 'required', 'string'],
            'marks' => ['nullable', 'integer', 'min:1'],
            'options' => ['sometimes', 'required', 'array', 'min:2', 'max:6'],
            'options.*.body' => ['required_with:options', 'string', 'max:1000'],
            'options.*.is_correct' => ['required_with:options', 'boolean'],
        ];
    }

    /**
     * Enforce exactly one correct option when options are being replaced.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if (! $this->has('options')) {
                return;
            }

            $options = (array) $this->input('options', []);
            $correct = collect($options)->filter(fn ($option) => filter_var($option['is_correct'] ?? false, FILTER_VALIDATE_BOOLEAN))->count();

            if ($correct !== 1) {
                $validator->errors()->add('options', 'Exactly one option must be marked correct.');
            }
        });
    }
}
