<?php

use App\Models\Post;
use App\Models\Tag;
use App\Models\User;

/*
 * Adding a category takes a NAME and nothing else, duplicates are refused by
 * the server (not by JavaScript), and removing one keeps it restorable.
 */

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
});

/* ------------------------------- adding ---------------------------------- */

it('offers an add dialog with a name field and no id or slug input', function () {
    $html = $this->actingAs($this->admin)->get(route('admin.categories.index'))->assertOk()->getContent();

    expect($html)->toContain('إضافة تصنيف')
        ->and($html)->toContain('name="name"')
        // The backend owns both of these — neither is ever asked for.
        ->and($html)->not->toContain('name="slug"')
        ->and($html)->not->toContain('name="id"');
});

it('creates a category from a name alone and generates the slug itself', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.categories.store'), ['name' => 'الابتكار والبحث'])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $tag = Tag::where('name', 'الابتكار والبحث')->first();

    expect($tag)->not->toBeNull()
        ->and($tag->slug)->not->toBe('')     // Arabic names still get a usable slug
        ->and($tag->id)->toBeGreaterThan(0);
});

it('ignores a slug someone tries to force through the form', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.categories.store'), ['name' => 'تصنيف', 'slug' => 'hand-picked'])
        ->assertRedirect();

    expect(Tag::where('slug', 'hand-picked')->exists())->toBeFalse()
        ->and(Tag::where('name', 'تصنيف')->exists())->toBeTrue();
});

/* ----------------------------- duplicates -------------------------------- */

it('refuses a duplicate category and says so', function () {
    Tag::create(['name' => 'تقنية', 'slug' => 'tech']);

    $this->actingAs($this->admin)
        ->post(route('admin.categories.store'), ['name' => 'تقنية'])
        ->assertRedirect()
        ->assertSessionHasErrors(['name' => 'هذا التصنيف موجود بالفعل.']);

    expect(Tag::where('name', 'تقنية')->count())->toBe(1);
});

it('treats stray spaces and case as the same category', function () {
    Tag::create(['name' => 'Vision 2030', 'slug' => 'vision']);

    foreach (['  Vision 2030 ', 'vision  2030', 'VISION 2030'] as $attempt) {
        $this->actingAs($this->admin)
            ->post(route('admin.categories.store'), ['name' => $attempt])
            ->assertSessionHasErrors('name');
    }

    expect(Tag::count())->toBe(1);
});

it('treats arabic spelling variants as the same category', function () {
    Tag::create(['name' => 'الحياة السعودية', 'slug' => 'life']);

    // ta-marbuta vs ha, and a bare alef vs an alef with hamza.
    $this->actingAs($this->admin)
        ->post(route('admin.categories.store'), ['name' => 'الحياه السعوديه'])
        ->assertSessionHasErrors('name');

    expect(Tag::count())->toBe(1);
});

it('refuses a duplicate on rename too', function () {
    Tag::create(['name' => 'اقتصاد', 'slug' => 'economy-1']);
    $other = Tag::create(['name' => 'تاريخ', 'slug' => 'history-1']);

    $this->actingAs($this->admin)
        ->put(route('admin.categories.update', $other), ['name' => 'اقتصاد'])
        ->assertSessionHasErrors('name');

    expect($other->fresh()->name)->toBe('تاريخ');
});

it('does not let an ordinary member add a category', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('admin.categories.store'), ['name' => 'مرفوض'])
        ->assertForbidden();

    $this->assertDatabaseMissing('tags', ['name' => 'مرفوض']);
});

/* ------------------------- restoring a selection -------------------------- */

it('lists the removed categories with a checkbox each', function () {
    $gone = Tag::create(['name' => 'سياحة', 'slug' => 'tourism']);
    $gone->delete();

    $html = $this->actingAs($this->admin)->get(route('admin.categories.index'))->assertOk()->getContent();

    expect($html)->toContain('استرداد التصنيفات المحذوفة')
        ->toContain('سياحة')
        ->toContain('name="ids[]"');
});

it('restores only the categories that were ticked', function () {
    $a = Tag::create(['name' => 'تقنية', 'slug' => 'tech']);
    $b = Tag::create(['name' => 'اقتصاد', 'slug' => 'economy']);
    $c = Tag::create(['name' => 'تاريخ', 'slug' => 'history']);
    $a->delete();
    $b->delete();
    $c->delete();

    $this->actingAs($this->admin)
        ->post(route('admin.categories.restore'), ['ids' => [$a->id, $b->id]])
        ->assertRedirect();

    expect(Tag::find($a->id))->not->toBeNull()
        ->and(Tag::find($b->id))->not->toBeNull()
        // Never ticked, so it stays removed.
        ->and(Tag::find($c->id))->toBeNull();
});

it('restores nothing when no category was selected', function () {
    $gone = Tag::create(['name' => 'سياحة', 'slug' => 'tourism']);
    $gone->delete();

    $this->actingAs($this->admin)
        ->post(route('admin.categories.restore'), [])
        ->assertRedirect()
        ->assertSessionHas('error', 'يرجى اختيار تصنيف واحد على الأقل.');

    expect(Tag::find($gone->id))->toBeNull();
});

it('brings a restored category back into the list writers pick from', function () {
    $tag = Tag::create(['name' => 'سياحة', 'slug' => 'tourism']);
    $tag->delete();

    expect(Tag::options())->not->toHaveKey('tourism');

    $this->actingAs($this->admin)->post(route('admin.categories.restore'), ['ids' => [$tag->id]]);

    expect(Tag::options())->toHaveKey('tourism');
});

it('keeps a restored category attached to its articles', function () {
    $tag = Tag::create(['name' => 'سياحة', 'slug' => 'tourism']);
    $post = Post::factory()->create();
    $post->tags()->attach($tag);

    $tag->delete();
    $this->actingAs($this->admin)->post(route('admin.categories.restore'), ['ids' => [$tag->id]]);

    expect($post->fresh()->tags->pluck('id'))->toContain($tag->id);
});

it('can restore a shipped default that has no row yet', function () {
    $slug = array_key_first(Tag::categories());

    $this->actingAs($this->admin)
        ->post(route('admin.categories.restore'), ['slugs' => [$slug]])
        ->assertRedirect();

    $this->assertDatabaseHas('tags', ['slug' => $slug]);
});

it('ignores a slug that is not one of the shipped defaults', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.categories.restore'), ['slugs' => ['not-a-real-default']])
        ->assertRedirect();

    $this->assertDatabaseMissing('tags', ['slug' => 'not-a-real-default']);
});

it('restores a removed category instead of creating a second one with the same name', function () {
    $tag = Tag::create(['name' => 'تقنية', 'slug' => 'tech']);
    $tag->delete();

    $this->actingAs($this->admin)
        ->post(route('admin.categories.store'), ['name' => 'تقنية'])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(Tag::withTrashed()->where('name', 'تقنية')->count())->toBe(1)
        ->and(Tag::find($tag->id))->not->toBeNull();
});
