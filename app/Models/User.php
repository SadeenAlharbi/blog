<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Post;
use App\Models\Comment;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /** Roles. Deliberately only two — an administrator and an ordinary author. */
    public const ROLE_ADMIN = 'admin';
    public const ROLE_USER = 'user';

    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function views()
    {
        return $this->hasMany(PostView::class);
    }

    /**
     * The single source of truth for "is this an administrator?".
     * Everything else (middleware, policies, views) asks this.
     */
    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    /**
     * The platform owner. Shares every moderation power with other admins, and
     * additionally is the only account that may grant or revoke administrator
     * access — so a promoted moderator can never mint further moderators.
     */
    public function isSuperAdmin(): bool
    {
        return $this->isAdmin() && (bool) $this->is_super_admin;
    }

    /** An administrator who was promoted from an ordinary account. */
    public function isPromotedAdmin(): bool
    {
        return $this->isAdmin() && ! $this->isSuperAdmin();
    }

    public function scopeAdmins($query)
    {
        return $query->where('role', self::ROLE_ADMIN);
    }

    public function roleLabel(): string
    {
        return match (true) {
            $this->isSuperAdmin() => 'المشرف الرئيسي',
            $this->isAdmin() => 'مشرف',
            default => 'كاتب',
        };
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'is_super_admin' => 'boolean',
        ];
    }
}
