<?php

namespace App\Notifications;

use App\Models\Comment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewCommentNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Comment $comment)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $post = $this->comment->post;

        return (new MailMessage)
            ->subject("New comment on \"{$post->title}\"")
            ->greeting("Hi {$notifiable->name},")
            ->line("{$this->comment->user->name} commented on your post \"{$post->title}\".")
            ->line($this->comment->content)
            ->action('View Post', url("/posts/{$post->slug}"))
            ->line('Thank you for using our platform!');
    }
}
