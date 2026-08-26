<?php

use App\Models\Comment;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;

/*
 * The admin screens the last round reshaped: no comment review queue, users
 * split into moderators and members, analytics in a fixed order, and the
 * public header/footer trimmed.
 */

beforeEach(function () {
    $this->admin = User::factory()->superAdmin()->create(['name' => 'المشرف الرئيسي']);
});

/** Just the <footer> of a rendered page. */
function footerHtml(string $html): string
{
    $start = mb_strpos($html, '<footer');

    return $start === false ? '' : mb_substr($html, $start);
}

/* ------------------------- comments: no review queue ---------------------- */

it('has no review state anywhere in the comment screens', function () {
    Comment::factory()->count(2)->create();

    $html = $this->actingAs($this->admin)->get(route('admin.comments.index'))->assertOk()->getContent();

    expect($html)->not->toContain('قيد المراجعة')
        ->and($html)->not->toContain('status=pending');
});

it('no longer exposes a review action or a review state', function () {
    expect(app('router')->getRoutes()->getByName('admin.comments.pending'))->toBeNull()
        ->and(Comment::statuses())->toBe([
            Comment::STATUS_APPROVED => 'ظاهر',
            Comment::STATUS_HIDDEN => 'مخفي',
        ])
        ->and(defined(Comment::class.'::STATUS_PENDING'))->toBeFalse();
});

it('publishes a new comment immediately, with no approval step', function () {
    $post = Post::factory()->create();

    $this->actingAs(User::factory()->create())
        ->post(route('comments.store', $post), ['content' => 'تعليق يظهر فوراً'])
        ->assertRedirect();

    // Visible to everyone, straight away.
    $this->get(route('posts.show', $post))->assertOk()->assertSee('تعليق يظهر فوراً');
});

it('keeps hide and delete working for moderators', function () {
    $comment = Comment::factory()->create(['content' => 'تعليق مخالف']);

    $this->actingAs($this->admin)->post(route('admin.comments.hide', $comment))->assertRedirect();
    expect($comment->fresh()->status)->toBe(Comment::STATUS_HIDDEN);

    $this->actingAs($this->admin)->post(route('admin.comments.approve', $comment))->assertRedirect();
    expect($comment->fresh()->status)->toBe(Comment::STATUS_APPROVED);

    $this->actingAs($this->admin)->delete(route('admin.comments.destroy', $comment))->assertRedirect();
    $this->assertSoftDeleted('comments', ['id' => $comment->id]);
});

/* ------------------------------ users screen ------------------------------ */

it('shows moderators first and members below, with no permissions note', function () {
    User::factory()->admin()->create(['name' => 'مشرف مساعد']);
    User::factory()->create(['name' => 'كاتب عادي']);

    $html = $this->actingAs($this->admin)->get(route('admin.users.index'))->assertOk()->getContent();

    // The section headings, not the page title (which is also "المستخدمون").
    $heading = '<h2 class="text-base font-bold text-ink-900">';
    $adminsSection = mb_strpos($html, $heading.'المشرفون');
    $membersSection = mb_strpos($html, $heading.'المستخدمون');

    expect($adminsSection)->not->toBeFalse()
        ->and($membersSection)->not->toBeFalse()
        // Moderators come first on the page.
        ->and($adminsSection)->toBeLessThan($membersSection)
        // The explanatory block is gone from the UI…
        ->and($html)->not->toContain('قواعد الصلاحيات');
});

it('keeps every column of user data on the page', function () {
    $member = User::factory()->create(['name' => 'كاتب عادي']);
    Post::factory()->create(['user_id' => $member->id]);
    Comment::factory()->create(['user_id' => $member->id]);

    $response = $this->actingAs($this->admin)->get(route('admin.users.index'))->assertOk();

    foreach (['المستخدم', 'البريد الإلكتروني', 'الدور', 'المقالات', 'التعليقات', 'تاريخ التسجيل', 'حالة الحساب', 'الإجراءات'] as $column) {
        $response->assertSee($column);
    }

    $response->assertSee($member->email)->assertSee('نشط');
});

it('shows a disabled account as disabled', function () {
    User::factory()->create(['name' => 'حساب معطل', 'is_active' => false]);

    $this->actingAs($this->admin)->get(route('admin.users.index'))->assertOk()->assertSee('معطّل');
});

it('still enforces the permission rules on the server after the note was removed', function () {
    $promoted = User::factory()->admin()->create();
    $member = User::factory()->create();

    // A promoted moderator still cannot create another one.
    $this->actingAs($promoted)
        ->put(route('admin.users.role', $member), ['role' => User::ROLE_ADMIN])
        ->assertForbidden();

    expect($member->fresh()->isAdmin())->toBeFalse();
});

/* ------------------------------- analytics -------------------------------- */

it('lays the analytics page out in the agreed order', function () {
    $post = Post::factory()->create();
    $post->tags()->attach(Tag::create(['name' => 'اقتصاد', 'slug' => 'economy-a']));

    $html = $this->actingAs($this->admin)->get(route('admin.analytics.index'))->assertOk()->getContent();

    $order = [
        'الإحصائيات العامة',
        'أكثر المقالات مشاهدة',
        'أكثر التصنيفات نشرًا',
        'أكثر التصنيفات مشاهدة',
        'المشاهدات عبر الزمن',
        'المقالات المنشورة عبر الزمن',
        'التعليقات عبر الزمن',
    ];

    $positions = array_map(fn ($heading) => mb_strpos($html, $heading), $order);

    foreach ($positions as $i => $position) {
        expect($position)->not->toBeFalse("missing section: {$order[$i]}");

        if ($i > 0) {
            expect($position)->toBeGreaterThan($positions[$i - 1], "out of order: {$order[$i]}");
        }
    }
});

it('fills the analytics overview from the database, not from constants', function () {
    Post::factory()->count(3)->create();
    Post::factory()->draft()->create();
    Comment::factory()->count(2)->create();

    $this->actingAs($this->admin)->get(route('admin.analytics.index'))
        ->assertOk()
        ->assertViewHas('overview', function ($overview) {
            return $overview['posts_total'] === Post::count()
                && $overview['posts_draft'] === Post::draft()->count()
                && $overview['comments_total'] === Comment::count()
                && $overview['users_total'] === User::count();
        });
});

it('derives category views from the article views already recorded', function () {
    $tag = Tag::create(['name' => 'سياحة', 'slug' => 'tourism-a']);
    $post = Post::factory()->create();
    $post->tags()->attach($tag);
    $post->views()->create(['ip_hash' => str_repeat('a', 64)]);
    $post->views()->create(['ip_hash' => str_repeat('b', 64)]);

    $this->actingAs($this->admin)->get(route('admin.analytics.index'))
        ->assertOk()
        ->assertViewHas('topCategoriesByViews', function ($categories) use ($tag) {
            $row = $categories->firstWhere('id', $tag->id);

            return $row && (int) $row->views_count === 2;
        });
});

/* --------------------------------- footer --------------------------------- */

it('keeps only the two article lists in the footer', function () {
    $html = $this->get('/')->assertOk()->getContent();
    $footer = mb_substr($html, (int) mb_strpos($html, '<footer'));

    expect($footer)->toContain('الأحدث نشرًا')
        ->and($footer)->toContain('الأكثر قراءة')
        // No navigation columns and no social links.
        ->and($footer)->not->toContain('جميع المقالات')
        ->and($footer)->not->toContain('linkedin')
        ->and($footer)->not->toContain('twitter')
        ->and($footer)->not->toContain('instagram')
        ->and($footer)->not->toContain('x.com');
});

it('lists exactly two articles per footer section, titles only', function () {
    $posts = collect(range(1, 4))->map(fn ($i) => Post::factory()->create([
        'title' => "مقال رقم {$i}",
        'published_at' => now()->subDays(10 - $i),
    ]));

    $posts->each(fn ($p) => $p->views()->create(['ip_hash' => str_repeat((string) $p->id, 64)]));

    cache()->forget('footer.lists');

    $footer = footerHtml($this->get('/')->assertOk()->getContent());

    // Two sections × two articles = four links, and nothing else.
    expect(substr_count($footer, 'href="'.url('/posts/')))->toBe(4)
        // No dates and no view counts beside the titles.
        ->and($footer)->not->toMatch('/\d{4}\/\d{2}\/\d{2}/')
        ->and($footer)->not->toContain('tabular-nums');
});

it('never puts a draft or a scheduled article in the footer', function () {
    Post::factory()->draft()->create(['title' => 'مسودة الفوتر']);
    Post::factory()->scheduled()->create(['title' => 'مجدول الفوتر']);
    Post::factory()->create(['title' => 'منشور فعلاً']);

    cache()->forget('footer.lists');

    $footer = footerHtml($this->get('/')->assertOk()->getContent());

    expect($footer)->toContain('منشور فعلاً')
        ->and($footer)->not->toContain('مسودة الفوتر')
        ->and($footer)->not->toContain('مجدول الفوتر');
});

/* --------------------------------- header --------------------------------- */

it('shows a green "إنشاء حساب" button to a guest', function () {
    $html = $this->get('/')->assertOk()->getContent();

    expect($html)->toContain('إنشاء حساب')
        ->and($html)->toContain('bg-brand-600 text-white px-4 py-2 text-sm font-semibold hover:bg-brand-700');
});

it('gives the logout button the same green styling once signed in', function () {
    $html = $this->actingAs(User::factory()->create())->get('/')->assertOk()->getContent();

    expect($html)->toContain('تسجيل خروج')
        // Byte-for-byte the class list the register button carries.
        ->and($html)->toContain('rounded-lg bg-brand-600 text-white px-4 py-2 text-sm font-semibold hover:bg-brand-700 transition-colors">تسجيل خروج');
});

it('still signs the user out when that button is used', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('logout'))->assertRedirect();

    expect(auth()->check())->toBeFalse();
});
