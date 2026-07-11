<?php

namespace Database\Factories;

use App\Enums\EnrollmentStatus;
use App\Enums\PaymentMethod;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Enrollment>
 */
class EnrollmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'course_id' => Course::factory(),
            'status' => EnrollmentStatus::Pending,
            'payment_method' => fake()->randomElement(PaymentMethod::cases()),
            'sender_number' => '01'.fake()->numerify('#########'),
            'receiver_number' => '01'.fake()->numerify('#########'),
            'transaction_id' => strtoupper(fake()->bothify('??######')),
            'amount' => fake()->numberBetween(500, 3000),
            'reviewed_at' => null,
            'reviewed_by' => null,
        ];
    }

    /**
     * An approved enrollment (grants course access).
     */
    public function approved(): static
    {
        return $this->state(fn (): array => [
            'status' => EnrollmentStatus::Approved,
            'reviewed_at' => now(),
        ]);
    }

    /**
     * A rejected enrollment.
     */
    public function rejected(): static
    {
        return $this->state(fn (): array => [
            'status' => EnrollmentStatus::Rejected,
            'reviewed_at' => now(),
        ]);
    }
}
