<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;
use App\Models\Post;

class Comment extends Model
{
    /** SoftDeletes — see the note on Post. */
    use HasFactory, SoftDeletes;

    /**
      * Comment visibility. There is NO review queue on this platform: a comment
      * is public the moment it is written, and a moderator may hide or delete
      * it afterwards. Only these two states exist.
      */
    public const STATUS_APPROVED = 'approved';
    public const STATUS_HIDDEN = 'hidden';

    protected $fillable = [
        'user_id',
        'post_id',
        'content',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function post()
    {
        return $this->belongsTo(Post::class);
    }

    /** Comments the public may read. */
    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopeHidden($query)
    {
        return $query->where('status', self::STATUS_HIDDEN);
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_HIDDEN => 'مخفي',
            default => 'ظاهر',
        };
    }

    public static function statuses(): array
    {
        return [
            self::STATUS_APPROVED => 'ظاهر',
            self::STATUS_HIDDEN => 'مخفي',
        ];
    }
}
