<?php

namespace Database\Factories;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Comment>
 */
class CommentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'post_id' => Post::factory(),
            'content' => fake()->sentence(12),
            'status' => Comment::STATUS_APPROVED,
        ];
    }

    public function hidden(): static
    {
        return $this->state(fn () => ['status' => Comment::STATUS_HIDDEN]);
    }

}
