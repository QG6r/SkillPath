<?php

namespace Database\Factories;

use App\Models\Submission;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Submission>
 */
class SubmissionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'task_id' => Task::factory(),
            'user_id' => User::factory(),
            'github_url' => 'https://github.com/student/project-'.fake()->unique()->numberBetween(1, 99999),
            'message' => fake()->sentence(),
            'status' => Submission::STATUS_PENDING,
        ];
    }

    public function correct(): static
    {
        return $this->state(fn (array $attributes) => ['status' => Submission::STATUS_CORRECT]);
    }

    public function incorrect(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Submission::STATUS_INCORRECT,
            'feedback' => 'Please fix the issues and send it again.',
        ]);
    }
}
