<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;
use App\Models\Comment;
use App\Models\Tag;


class Post extends Model
{
    /**
     * SoftDeletes: a removed article keeps its row so the site can say
     * "this was removed by the moderators" instead of returning a 404, and so
     * its relations survive. Every normal query still excludes trashed rows.
     */
    use HasFactory, SoftDeletes;

    /** Publishing workflow states. */
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_SCHEDULED = 'scheduled';

    protected $fillable = [
        'user_id',
        'title',
        'slug',
        'content',
        'image',
        'status',
        'published_at',
    ];

    /**
     * published_at is a real date column; cast it so ->format() works and
     * dates render everywhere (previously it came back as a raw string, which
     * crashed the edit form and silently blanked dates via optional()).
     */
    protected $casts = [
        'published_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class);
    }

    public function views()
    {
        return $this->hasMany(PostView::class);
    }

    /* ------------------------------------------------------------------ *
     | Scopes
     * ------------------------------------------------------------------ */

    /**
     * Publicly visible posts: explicitly published, and not dated in the
     * future. Every public-facing query (site + API) goes through this.
     */
    public function scopePublished($query)
    {
        return $query->where('status', self::STATUS_PUBLISHED)
            ->where(function ($q) {
                $q->whereNull('published_at')->orWhere('published_at', '<=', now());
            });
    }

    public function scopeDraft($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', self::STATUS_SCHEDULED);
    }

    /* ------------------------------------------------------------------ *
     | Status helpers (used by the admin UI badges)
     * ------------------------------------------------------------------ */

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED
            && (is_null($this->published_at) || $this->published_at->lte(now()));
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isScheduled(): bool
    {
        return $this->status === self::STATUS_SCHEDULED;
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'مسودة',
            self::STATUS_SCHEDULED => 'مجدول',
            default => 'منشور',
        };
    }

    /**
     * Should the public site credit an author on this article?
     *
     * Articles published by the platform's own moderators read as institutional
     * content, so no personal byline is shown for them. The relation and
     * `user_id` are untouched — this is a presentation rule only, and the admin
     * area still shows who wrote what.
     */
    public function showsAuthor(): bool
    {
        return $this->user !== null && ! $this->user->isAdmin();
    }

    public static function statuses(): array
    {
        return [
            self::STATUS_PUBLISHED => 'منشور',
            self::STATUS_DRAFT => 'مسودة',
            self::STATUS_SCHEDULED => 'مجدول',
        ];
    }
}
