<?php

namespace Database\Seeders;

use App\Models\Comment;
use App\Models\Post;
use App\Models\PostView;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $testUser = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $authors = User::factory(9)->create()->push($testUser);

        $tags = collect(Tag::categories())->map(
            fn ($name, $slug) => Tag::firstOrCreate(['slug' => $slug], ['name' => $name])
        )->values();

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

                
                foreach (range(1, random_int(0, 40)) as $i) {
                    PostView::create([
                        'post_id' => $post->id,
                        'user_id' => random_int(0, 1) ? $authors->random()->id : null,
                        'ip_hash' => hash('sha256', fake()->ipv4().$i),
                        'user_agent' => 'Seeder',
                        'created_at' => $when = fake()->dateTimeBetween('-60 days', 'now'),
                        'updated_at' => $when,
                    ]);
                }
            });

        Post::factory(4)->draft()->recycle($authors)->create();
        Post::factory(3)->scheduled()->recycle($authors)->create();


        $this->call(AdminUserSeeder::class);
    }
}
