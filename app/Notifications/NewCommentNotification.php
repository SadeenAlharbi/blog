<?php

namespace App\Notifications;

use App\Models\Comment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

/**
 * Sent to a post's author when someone else comments on their post.
 *
 * Two channels, one notification (so there is never a duplicate):
 *   - mail     → an Arabic, RTL, branded email (delivered asynchronously)
 *   - database → an in-site record shown in the notification bell (read_at aware)
 *
 * Implements ShouldQueue so BOTH channels are handled by the queue worker —
 * adding a comment returns immediately and never waits on (or fails because of)
 * SMTP. On a mail failure the job is retried ($tries) and, once the attempts are
 * exhausted, recorded in the `failed_jobs` table.
 */
class NewCommentNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /** Retry the queued notification a few times before it lands in failed_jobs. */
    public int $tries = 3;

    /** Seconds to wait between retries. */
    public int $backoff = 30;

    public function __construct(private readonly Comment $comment)
    {
    }

    /**
     * Deliver the in-site record IMMEDIATELY, and only e-mail through the queue.
     *
     * This project runs QUEUE_CONNECTION=database. With the whole notification
     * queued, every bell record sat unprocessed in the `jobs` table until
     * someone ran `php artisan queue:work` — which is why notifications
     * appeared not to work at all. Pinning the `database` channel to the `sync`
     * connection writes the record during the request, so the bell is correct
     * whether or not a worker is running, while mail stays asynchronous and
     * never blocks the response.
     */
    public function viaConnections(): array
    {
        return ['database' => 'sync'];
    }

    /**
     * Delivery channels: in-site bell (database) + email (mail).
     *
     * Guard against orphaned data: if the comment or its post no longer exists
     * by the time the queued job runs (e.g. the post was deleted), deliver
     * nothing instead of throwing — the job completes cleanly rather than
     * piling up in failed_jobs.
     */
    public function via(object $notifiable): array
    {
        if (! $this->comment || ! $this->comment->post) {
            return [];
        }

        return ['database', 'mail'];
    }

    /**
     * Deep-link straight to the comment on the post page (where the owner reads
     * comments). Uses the existing named route + slug binding, not a hand-built path.
     */
    private function commentUrl(): string
    {
        return route('posts.show', $this->comment->post).'#comment-'.$this->comment->id;
    }

    /**
     * Payload stored for the `database` channel and rendered by the bell/index.
     * All values are dynamic.
     */
    public function toArray(object $notifiable): array
    {
        $post = $this->comment->post;

        return [
            // Explicit type/action so the UI can tell this apart from an
            // article notice instead of guessing from the payload's shape.
            'type' => 'comment',
            'action' => 'comment_created',
            'comment_id' => $this->comment->id,
            'post_id' => $post->id,
            'post_slug' => $post->slug,
            'post_title' => $post->title,
            'commenter_name' => $this->comment->user?->name ?? 'مستخدم',
            'excerpt' => Str::limit($this->comment->content, 140),
            'url' => $this->commentUrl(),
        ];
    }

    /**
     * Build the Arabic, RTL, branded email. Rendered by a dedicated Blade view
     * so tests can assert on the view data.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $post = $this->comment->post;

        return (new MailMessage)
            ->subject('تعليق جديد على مقالك')
            ->view('emails.comment-added', [
                'ownerName' => $notifiable->name,
                'post' => $post,
                'comment' => $this->comment,
                'commenterName' => $this->comment->user?->name ?? 'مستخدم',
                'commentExcerpt' => Str::limit($this->comment->content, 400),
                'url' => $this->commentUrl(),
            ]);
    }
}
