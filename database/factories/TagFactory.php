<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<\App\Models\Tag>
 */
class TagFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->randomElement([
            'Vision 2030', 'Digital Transformation', 'Artificial Intelligence',
            'Tourism', 'Heritage', 'NEOM', 'Economy', 'Technology',
            'Culture', 'Society', 'National Projects', 'Renewable Energy',
            'Education', 'Entrepreneurship', 'Sports', 'Giga Projects',
        ]);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
        ];
    }
}
