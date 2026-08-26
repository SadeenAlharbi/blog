<?php

use App\Models\Post;

/*
 * The article list is a React island, so its markup is asserted against the
 * component source: the point of the fix is that "اقرأ المزيد" lives INSIDE
 * the card element, not as a sibling below it in the grid.
 */

function explorerSource(): string
{
    return file_get_contents(resource_path('js/components/PostsExplorer.jsx'));
}

/** The component source with its comments removed, so prose cannot match. */
function explorerMarkup(): string
{
    $src = explorerSource();
    $src = preg_replace('#\{/\*.*?\*/\}#s', '', $src);   // JSX comments
    $src = preg_replace('#/\*.*?\*/#s', '', $src);         // block comments

    return preg_replace('#^\s*//.*$#m', '', $src);          // line comments
}

it('renders the read-more button inside the card element', function () {
    $src = explorerMarkup();

    $cardOpens = mb_strpos($src, '<article');
    $cardCloses = mb_strpos($src, '</article>');
    $button = mb_strpos($src, 'اقرأ المزيد');

    expect($cardOpens)->not->toBeFalse()
        ->and($button)->not->toBeFalse()
        // Strictly between the card's own opening and closing tags.
        ->and($button)->toBeGreaterThan($cardOpens)
        ->and($button)->toBeLessThan($cardCloses);
});

it('no longer hands the button to the design-system card', function () {
    $src = explorerSource();

    // DgaCard closed its own box before the button could be added, which is
    // what pushed the button out of the card and into the grid.
    expect($src)->not->toContain('<DgaCard')
        ->and($src)->not->toContain('primaryActionLabel');
});

it('keeps every card the same height with the button pinned to the bottom', function () {
    $src = explorerSource();

    expect($src)->toContain('items-stretch')      // grid rows stretch
        ->toContain('flex h-full cursor-pointer flex-col')  // card fills its row
        ->toContain('flex min-w-0 flex-1 flex-col')  // body grows
        ->toContain('mt-auto');                   // footer sits at the bottom
});

it('keeps the button on the project button style', function () {
    $src = explorerSource();

    // The same class list the header's "إنشاء حساب" button carries.
    expect($src)->toContain('rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-brand-700');
});

it('points each button at its own article', function () {
    $src = explorerSource();

    expect($src)->toContain('const url = `/posts/${post.slug}`')
        ->and($src)->toContain('href={url}')
        // Clicking the button must not also fire the card's own handler.
        ->and($src)->toContain('onClick={(e) => e.stopPropagation()}');
});

it('stays responsive and free of horizontal overflow', function () {
    $src = explorerMarkup();

    expect($src)->toContain('grid gap-6 sm:grid-cols-2 lg:grid-cols-3')  // 1 / 2 / 3 columns
        ->toContain('overflow-hidden')          // the image cannot spill out
        ->toContain('min-w-0');                 // long words cannot widen a cell
});

/* ------------------------- the server-rendered path ----------------------- */

it('still serves the article list and its no-script card fallback', function () {
    $post = Post::factory()->create(['title' => 'مقال للعرض']);

    $this->get(route('posts.index'))
        ->assertOk()
        ->assertSee('posts-explorer-root', false)
        ->assertSee('مقال للعرض');
});

it('keeps the article route working', function () {
    $post = Post::factory()->create();

    $this->get(route('posts.show', $post))->assertOk()->assertSee($post->title);
});

it('feeds the card everything it draws', function () {
    $post = Post::factory()->create();

    $this->getJson('/api/v1/posts')
        ->assertOk()
        ->assertJsonStructure(['data' => [['id', 'title', 'slug', 'content', 'image_url', 'published_at', 'tags']]]);
});
