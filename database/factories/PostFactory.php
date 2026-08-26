<?php

namespace Database\Factories;

use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<\App\Models\Post>
 */
class PostFactory extends Factory
{
    public function definition(): array
    {
        $titles = [
            'Saudi Vision 2030: A Roadmap for National Transformation',
            'How NEOM Is Redefining Urban Development in Saudi Arabia',
            'The Rise of Artificial Intelligence in the Kingdom',
            'Saudi Arabia\'s Digital Transformation Journey',
            'Exploring the Heritage of AlUla',
            'Tourism Growth: Saudi Arabia Opens to the World',
            'The Kingdom\'s Path Toward a Diversified Economy',
            'National Projects Shaping Saudi Arabia\'s Future',
            'Saudi Society in an Era of Rapid Change',
            'Investing in Technology: Saudi Arabia\'s Innovation Push',
            'Renewable Energy Initiatives Across the Kingdom',
            'Saudi Arabia\'s Achievements on the Global Stage',
        ];

        $title = fake()->randomElement($titles).' '.Str::random(8);

        return [
            'user_id' => User::factory(),
            'title' => $title,
            'slug' => Str::slug($title),
            'content' => implode("\n\n", fake()->paragraphs(6)),
            'image' => null,
            // Published by default so existing behaviour and tests are unchanged.
            'status' => Post::STATUS_PUBLISHED,
            'published_at' => fake()->dateTimeBetween('-1 year', 'now'),
        ];
    }

    /** A saved-but-unpublished article. */
    public function draft(): static
    {
        return $this->state(fn () => [
            'status' => Post::STATUS_DRAFT,
        ]);
    }

    /** An article queued for a future moment. */
    public function scheduled(): static
    {
        return $this->state(fn () => [
            'status' => Post::STATUS_SCHEDULED,
            'published_at' => now()->addDays(3),
        ]);
    }
}
