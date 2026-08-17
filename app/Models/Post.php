<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Comment;
use App\Models\Tag;


class Post extends Model
{
protected $fillable = [
    'user_id',
    'title',
    'slug',
    'content',
    'image',
    'published_at',
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
}
