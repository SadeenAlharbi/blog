<?php

use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/*
 * Guards for three defects found during the code review. Each one asserts the
 * behaviour that was broken, so the same mistake cannot come back.
 */

/* ---------------- the confirmation dialog on destructive forms ------------- */

it('defines the confirmation modal before the script that binds to it', function () {
    $layout = file_get_contents(resource_path('views/layouts/admin.blade.php'));

    $modal = mb_strpos($layout, 'id="confirm-modal"');
    $script = mb_strpos($layout, "getElementById('confirm-modal')");

    expect($modal)->not->toBeFalse()
        ->and($script)->not->toBeFalse()
        // The script bailed out on a null element when it ran first, which
        // silently disabled the confirmation on every destructive admin form.
        ->and($modal)->toBeLessThan($script);
});

it('still asks for confirmation on the destructive admin forms', function () {
    $admin = User::factory()->superAdmin()->create();
    $post = Post::factory()->create();

    $html = $this->actingAs($admin)->get(route('admin.posts.index'))->assertOk()->getContent();

    expect($html)->toContain('data-confirm')
        ->and($html)->toContain('id="confirm-modal"');
});

/* ------------- attaching a category a moderator had removed --------------- */

it('does not fail when a submitted category was removed', function () {
    $slug = array_key_first(Tag::categories());
    $tag = Tag::create(['slug' => $slug, 'name' => Tag::categories()[$slug]]);
    $tag->delete();

    $user = User::factory()->create();

    // tags.slug and tags.name are unique, so re-creating the removed row threw
    // an integrity violation and the request 500'd.
    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/posts', ['title' => 'مقال', 'content' => 'محتوى', 'tags' => [$slug]])
        ->assertCreated();

    // The removed category stays removed and is not attached.
    expect(Post::latest('id')->first()->tags)->toHaveCount(0)
        ->and(Tag::withTrashed()->where('slug', $slug)->count())->toBe(1);
});

it('still attaches a category that is in use', function () {
    $slug = array_key_first(Tag::categories());
    Tag::create(['slug' => $slug, 'name' => Tag::categories()[$slug]]);

    $this->actingAs(User::factory()->create(), 'sanctum')
        ->postJson('/api/v1/posts', ['title' => 'مقال', 'content' => 'محتوى', 'tags' => [$slug]])
        ->assertCreated();

    expect(Post::latest('id')->first()->tags->pluck('slug')->all())->toBe([$slug]);
});

it('creates a canonical category the first time it is used', function () {
    $slug = array_key_first(Tag::categories());

    $this->actingAs(User::factory()->create(), 'sanctum')
        ->postJson('/api/v1/posts', ['title' => 'مقال', 'content' => 'محتوى', 'tags' => [$slug]])
        ->assertCreated();

    $this->assertDatabaseHas('tags', ['slug' => $slug]);
});

/* --------------------- the dashboard's repeated counts -------------------- */

it('counts drafts, scheduled posts and hidden comments once each', function () {
    $admin = User::factory()->superAdmin()->create();
    Post::factory()->draft()->create();
    Post::factory()->scheduled()->create();

    DB::flushQueryLog();
    DB::enableQueryLog();
    $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();
    $log = DB::getQueryLog();
    DB::disableQueryLog();

    $counts = [];
    foreach ($log as $q) {
        $key = $q['query'].'|'.json_encode($q['bindings']);
        $counts[$key] = ($counts[$key] ?? 0) + 1;
    }

    $repeatedCounts = array_filter(
        $counts,
        fn ($n, $key) => $n > 1 && str_contains($key, 'count(*)'),
        ARRAY_FILTER_USE_BOTH
    );

    expect($repeatedCounts)->toBeEmpty();
});
