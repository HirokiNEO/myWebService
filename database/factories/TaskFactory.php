<?php

namespace Database\Factories;

use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition(): array
    {
        $start = fake()->dateTimeBetween('now', '+1 month');
        $end = (clone $start)->modify('+1 hour');

        return [
            'user_id' => User::factory(),
            'title' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'start_at' => $start,
            'end_at' => $end,
            'is_all_day' => false,
            'color' => fake()->randomElement(['#4f46e5', '#2563eb', '#059669', '#d97706', '#dc2626']),
            'is_completed' => false,
        ];
    }
}
