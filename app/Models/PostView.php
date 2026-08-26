<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One recorded read of an article. Written only by PostViewService, which
 * applies the de-duplication window — nothing else should insert here.
 */
class PostView extends Model
{
    protected $fillable = [
        'post_id',
        'user_id',
        'ip_hash',
        'user_agent',
    ];

    public function post()
    {
        return $this->belongsTo(Post::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
