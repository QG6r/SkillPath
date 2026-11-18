<?php

namespace Database\Factories;

use App\Models\Module;
use App\Models\Topic;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Topic>
 */
class TopicFactory extends Factory
{
    public function definition(): array
    {
        return [
            'module_id' => Module::factory(),
            'title' => fake()->sentence(3),
            'content' => fake()->paragraph(),
            'video_url' => null,
            'order' => 1,
        ];
    }
}
