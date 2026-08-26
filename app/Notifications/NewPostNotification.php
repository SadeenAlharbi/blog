<?php

namespace App\Notifications;

use App\Models\Post;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

/**
 * Sent to the platform's moderators when a MEMBER publishes an article.
 *
 * Deliberately database-only and NOT queued: moderators read this in the bell,
 * and an in-site record should never depend on a queue worker running. (See
 * NewCommentNotification::viaConnections() for the same reasoning.)
 */
class NewPostNotification extends Notification
{
    public function __construct(private readonly Post $post)
    {
    }

    public function via(object $notifiable): array
    {
        return $this->post ? ['database'] : [];
    }

    public function toArray(object $notifiable): array
    {
        $author = $this->post->user?->name ?? 'أحد الأعضاء';

        return [
            'type' => 'post',
            'action' => 'post_published',
            'post_id' => $this->post->id,
            'post_slug' => $this->post->slug,
            'post_title' => $this->post->title,
            'author_name' => $author,
            'title' => $this->post->title,
            'message' => "تم نشر مقال جديد بواسطة {$author}.",
            'excerpt' => Str::limit(strip_tags($this->post->content), 140),
            // Straight into the moderator's own view of the article.
            'url' => route('admin.posts.show', $this->post->id),
        ];
    }
}
