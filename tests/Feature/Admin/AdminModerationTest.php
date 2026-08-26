<?php

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;

/* --------------------------- comment moderation --------------------------- */

it('lets an admin hide and re-approve a comment', function () {
    $admin = User::factory()->admin()->create();
    $comment = Comment::factory()->create();

    $this->actingAs($admin)->post(route('admin.comments.hide', $comment))->assertRedirect();
    expect($comment->fresh()->status)->toBe(Comment::STATUS_HIDDEN);

    $this->actingAs($admin)->post(route('admin.comments.approve', $comment))->assertRedirect();
    expect($comment->fresh()->status)->toBe(Comment::STATUS_APPROVED);
});

it('hides a hidden comment from the public article page', function () {
    $post = Post::factory()->create();
    Comment::factory()->create(['post_id' => $post->id, 'content' => 'تعليق ظاهر']);
    Comment::factory()->hidden()->create(['post_id' => $post->id, 'content' => 'تعليق مخفي']);

    $this->get(route('posts.show', $post))
        ->assertOk()
        ->assertSee('تعليق ظاهر')
        ->assertDontSee('تعليق مخفي');
});

it('does not let an ordinary user moderate comments', function () {
    $user = User::factory()->create();
    $comment = Comment::factory()->create();

    $this->actingAs($user)->post(route('admin.comments.hide', $comment))->assertForbidden();
    expect($comment->fresh()->status)->toBe(Comment::STATUS_APPROVED);
});

it('lets an admin delete anyone\'s comment but a user only their own', function () {
    $admin = User::factory()->admin()->create();
    $owner = User::factory()->create();
    $other = User::factory()->create();

    $a = Comment::factory()->create(['user_id' => $owner->id]);
    $b = Comment::factory()->create(['user_id' => $owner->id]);

    // Another ordinary user cannot delete it.
    $this->actingAs($other)->delete(route('comments.destroy', $a))->assertForbidden();
    $this->assertDatabaseHas('comments', ['id' => $a->id]);

    // The owner can.
    $this->actingAs($owner)->delete(route('comments.destroy', $a))->assertRedirect();
    $this->assertSoftDeleted('comments', ['id' => $a->id]);

    // The admin can delete someone else's.
    $this->actingAs($admin)->delete(route('admin.comments.destroy', $b))->assertRedirect();
    $this->assertSoftDeleted('comments', ['id' => $b->id]);
});

/* ----------------------------- user management ---------------------------- */

it('lets the platform owner promote a member to admin', function () {
    $owner = User::factory()->superAdmin()->create();
    $user = User::factory()->create();

    $this->actingAs($owner)
        ->put(route('admin.users.role', $user), ['role' => User::ROLE_ADMIN])
        ->assertRedirect();

    expect($user->fresh()->isAdmin())->toBeTrue();
});

it('does not let a promoted admin promote anyone else', function () {
    $promoted = User::factory()->admin()->create();   // admin, but not the owner
    $user = User::factory()->create();

    $this->actingAs($promoted)
        ->put(route('admin.users.role', $user), ['role' => User::ROLE_ADMIN])
        ->assertForbidden();

    expect($user->fresh()->isAdmin())->toBeFalse();
});

it('does not let a promoted admin change another admin\'s role', function () {
    $promoted = User::factory()->admin()->create();
    $otherAdmin = User::factory()->admin()->create();

    $this->actingAs($promoted)
        ->put(route('admin.users.role', $otherAdmin), ['role' => User::ROLE_USER])
        ->assertForbidden();

    expect($otherAdmin->fresh()->isAdmin())->toBeTrue();
});

it('does not let anyone change the platform owner\'s role', function () {
    $owner = User::factory()->superAdmin()->create();
    $secondOwnerAttempt = User::factory()->superAdmin()->create();

    $this->actingAs($secondOwnerAttempt)
        ->put(route('admin.users.role', $owner), ['role' => User::ROLE_USER])
        ->assertForbidden();

    expect($owner->fresh()->isAdmin())->toBeTrue();
});

it('does not let an admin change their own role', function () {
    $owner = User::factory()->superAdmin()->create();

    $this->actingAs($owner)
        ->put(route('admin.users.role', $owner), ['role' => User::ROLE_USER])
        ->assertForbidden();

    expect($owner->fresh()->isAdmin())->toBeTrue();
});

it('does not let an ordinary user change roles', function () {
    $user = User::factory()->create();
    $target = User::factory()->create();

    $this->actingAs($user)
        ->put(route('admin.users.role', $target), ['role' => User::ROLE_ADMIN])
        ->assertForbidden();

    expect($target->fresh()->isAdmin())->toBeFalse();
});

/* ------------------------------ deactivation ------------------------------ */

it('lets a promoted admin deactivate an ordinary member', function () {
    $promoted = User::factory()->admin()->create();
    $user = User::factory()->create();

    $this->actingAs($promoted)->post(route('admin.users.toggleActive', $user))->assertRedirect();

    expect($user->fresh()->is_active)->toBeFalse();
});

it('does not let a promoted admin deactivate another admin', function () {
    $promoted = User::factory()->admin()->create();
    $otherAdmin = User::factory()->admin()->create();

    $this->actingAs($promoted)
        ->post(route('admin.users.toggleActive', $otherAdmin))
        ->assertForbidden();

    expect($otherAdmin->fresh()->is_active)->toBeTrue();
});

it('does not let anyone deactivate the platform owner', function () {
    $owner = User::factory()->superAdmin()->create();
    $promoted = User::factory()->admin()->create();

    $this->actingAs($promoted)
        ->post(route('admin.users.toggleActive', $owner))
        ->assertForbidden();

    expect($owner->fresh()->is_active)->toBeTrue();
});

it('lets the owner deactivate a promoted admin', function () {
    $owner = User::factory()->superAdmin()->create();
    $promoted = User::factory()->admin()->create();

    $this->actingAs($owner)->post(route('admin.users.toggleActive', $promoted))->assertRedirect();

    expect($promoted->fresh()->is_active)->toBeFalse();
});

/**
 * Defence in depth: the policy refuses to strip the LAST active administrator,
 * so the platform can never be left without one.
 */
it('refuses to demote the last active admin', function () {
    $onlyOwner = User::factory()->superAdmin()->create();
    $inactiveOwner = User::factory()->superAdmin()->inactive()->create();

    $policy = new \App\Policies\UserPolicy();

    // The owner is protected outright, and there is no other active admin.
    expect($policy->updateRole($inactiveOwner, $onlyOwner))->toBeFalse()
        ->and($policy->toggleActive($inactiveOwner, $onlyOwner))->toBeFalse();
});

it('keeps day-to-day moderation open to a promoted admin', function () {
    $promoted = User::factory()->admin()->create();

    // Articles, comments and categories are all still reachable.
    $this->actingAs($promoted)->get(route('admin.posts.index'))->assertOk();
    $this->actingAs($promoted)->get(route('admin.comments.index'))->assertOk();
    $this->actingAs($promoted)->get(route('admin.categories.index'))->assertOk();
    $this->actingAs($promoted)->get(route('admin.users.index'))->assertOk();
});
