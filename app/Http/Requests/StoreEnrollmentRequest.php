<?php

namespace App\Http\Requests;

use App\Enums\PaymentMethod;
use App\Models\Course;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreEnrollmentRequest extends FormRequest
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
     * Payment details are required for paid courses; free courses enroll
     * instantly with no payment information.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $required = $this->paidCourse() ? 'required' : 'nullable';

        return [
            'payment_method' => [$required, new Enum(PaymentMethod::class)],
            'sender_number' => [$required, 'string', 'max:32'],
            'receiver_number' => [$required, 'string', 'max:32'],
            'transaction_id' => [$required, 'string', 'max:64'],
        ];
    }

    /**
     * Whether the course being enrolled in requires payment.
     */
    private function paidCourse(): bool
    {
        $course = $this->route('course');

        return $course instanceof Course && ! $course->isFree();
    }
}
