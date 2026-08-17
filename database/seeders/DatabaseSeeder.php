<?php

namespace Database\Seeders;

use App\Models\Comment;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $testUser = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $authors = User::factory(9)->create()->push($testUser);

        $tags = Tag::factory(12)->create();

        Post::factory(30)
            ->recycle($authors)
            ->create()
            ->each(function (Post $post) use ($tags, $authors) {
                $post->tags()->attach(
                    $tags->random(random_int(1, 3))->pluck('id')
                );

                Comment::factory(random_int(0, 5))
                    ->recycle($authors)
                    ->create(['post_id' => $post->id]);
            });
    }
}
