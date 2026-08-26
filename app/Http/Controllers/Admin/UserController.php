<?php

namespace App\Http\Controllers\Admin;

use App\Events\UserAccountChangedByAdmin;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateUserRoleRequest;
use App\Models\Post;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query()
            ->withCount([
                'posts',
                'posts as published_posts_count' => fn ($q) => $q->where('status', Post::STATUS_PUBLISHED),
                'comments',
            ]);

        if ($search = $request->string('search')->trim()->value()) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($role = $request->string('role')->trim()->value()) {
            if (in_array($role, [User::ROLE_ADMIN, User::ROLE_USER], true)) {
                $query->where('role', $role);
            }
        }

        /*
         * The page shows moderators first, then members, as two separate
         * sections. Moderators are a short list, so they come back whole;
         * members stay paginated because that list grows.
         */
        $admins = (clone $query)->where('role', User::ROLE_ADMIN)
            ->orderByDesc('is_super_admin')
            ->orderBy('name')
            ->get();

        $members = (clone $query)->where('role', '!=', User::ROLE_ADMIN)
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.users.index', [
            'admins' => $admins,
            'members' => $members,
            'filters' => $request->only(['search', 'role']),
            'counts' => [
                'all' => User::count(),
                'admins' => User::admins()->count(),
                'active' => User::where('is_active', true)->count(),
            ],
        ]);
    }

    public function show(User $user)
    {
        $this->authorize('manage', User::class);

        $user->loadCount(['posts', 'comments']);

        return view('admin.users.show', [
            'user' => $user,
            'posts' => $user->posts()->withCount(['comments', 'views'])->latest()->paginate(10),
        ]);
    }

    /**
     * Change a user's role. UserPolicy blocks self-changes and refuses to
     * demote the last remaining active administrator.
     */
    public function updateRole(UpdateUserRoleRequest $request, User $user)
    {
        $this->authorize('updateRole', $user);

        // Assigned explicitly, NOT via update(): `role` is deliberately absent
        // from the User model's fillable list, so no mass-assignment path
        // (registration, profile edit, API) can ever escalate a privilege.
        $user->role = $request->validated('role');
        $user->save();

        // Let the member know their permissions changed.
        UserAccountChangedByAdmin::dispatch(
            $user,
            $request->user(),
            UserAccountChangedByAdmin::ACTION_ROLE_CHANGED
        );

        return back()->with('success', "تم تحديث دور «{$user->name}».");
    }

    /** Activate / deactivate an account (same safety rules as role changes). */
    public function toggleActive(Request $request, User $user)
    {
        $this->authorize('toggleActive', $user);

        // Explicit assignment for the same reason as updateRole().
        $user->is_active = ! $user->is_active;
        $user->save();

        UserAccountChangedByAdmin::dispatch(
            $user,
            $request->user(),
            $user->is_active
                ? UserAccountChangedByAdmin::ACTION_ACTIVATED
                : UserAccountChangedByAdmin::ACTION_DEACTIVATED
        );

        return back()->with(
            'success',
            $user->is_active ? "تم تفعيل حساب «{$user->name}»." : "تم تعطيل حساب «{$user->name}»."
        );
    }
}
