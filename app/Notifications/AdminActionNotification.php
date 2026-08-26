<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells a member that a moderator acted on their content or account.
 *
 * One class, one message table — every Arabic string lives here rather than in
 * a controller, so the wording is defined in exactly one place. It follows the
 * project's existing notification shape (ShouldQueue, `database` for the bell)
 * and reuses the same `data` keys the bell already renders.
 *
 * Channels are chosen per action: everything reaches the in-site bell, and only
 * the consequential actions (content removed, account changed) also send an
 * email — routine moderation should not fill an inbox.
 */
class AdminActionNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 30;

    /** Actions that additionally deserve an email, not just the bell. */
    private const MAILED_ACTIONS = [
        'post_deleted',
        'comment_deleted',
        'role_changed',
        'account_deactivated',
    ];

    /**
     * @param  string  $action   one of the ACTION_* constants on the events
     * @param  array   $context  ['title' => ..., 'excerpt' => ..., 'url' => ...]
     */
    public function __construct(
        private readonly string $action,
        private readonly array $context = [],
    ) {
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

    public function via(object $notifiable): array
    {
        return in_array($this->action, self::MAILED_ACTIONS, true)
            ? ['database', 'mail']
            : ['database'];
    }

    /** The Arabic sentence shown for each action. */
    private function message(): string
    {
        $title = $this->context['title'] ?? '';

        return match ($this->action) {
            'post_deleted' => $title !== ''
                ? "تم حذف مقالك «{$title}» من قبل الإدارة."
                : 'تم حذف مقالك من قبل الإدارة.',
            'post_updated' => $title !== ''
                ? "تم تعديل مقالك «{$title}» من قبل الإدارة."
                : 'تم تعديل مقالك من قبل الإدارة.',
            'comment_deleted' => 'تم حذف تعليقك من قبل الإدارة.',
            'comment_hidden' => 'تم إخفاء تعليقك من قبل الإدارة.',
            'comment_approved' => 'تمت الموافقة على تعليقك وأصبح ظاهراً.',
            'role_changed' => 'تم تحديث صلاحيات حسابك من قبل الإدارة.',
            'account_activated' => 'تم تفعيل حسابك من قبل الإدارة.',
            'account_deactivated' => 'تم تعطيل حسابك من قبل الإدارة.',
            default => 'تم تنفيذ إجراء إداري على حسابك.',
        };
    }

    /** A short subject line for the actions that are emailed. */
    private function subject(): string
    {
        return match ($this->action) {
            'post_deleted' => 'تم حذف مقالك',
            'comment_deleted' => 'تم حذف تعليقك',
            'role_changed' => 'تم تحديث صلاحيات حسابك',
            'account_deactivated' => 'تم تعطيل حسابك',
            default => 'إشعار من إدارة المنصة',
        };
    }

    /**
     * Bell payload. `message` is the key the bell renders; the older
     * comment notification's keys are still rendered too, so both shapes
     * display correctly side by side.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'admin_action',
            'action' => $this->action,
            'message' => $this->message(),
            'title' => $this->context['title'] ?? null,
            'excerpt' => $this->context['excerpt'] ?? null,
            'url' => $this->context['url'] ?? null,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject($this->subject())
            ->greeting("مرحباً {$notifiable->name},")
            ->line($this->message());

        if (! empty($this->context['excerpt'])) {
            $mail->line($this->context['excerpt']);
        }

        if (! empty($this->context['url'])) {
            $mail->action('فتح المنصة', $this->context['url']);
        }

        return $mail->line('إذا كان لديك استفسار حول هذا الإجراء، يرجى التواصل مع إدارة المنصة.');
    }
}
