<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Turns a stored notification payload into what the UI needs to draw it:
 * an icon name, a colour tone, a sentence, and the article/subject it is about.
 *
 * ONE place decides this, and all three surfaces (the header bell, the member's
 * notifications page, the admin notifications page) read from it — previously
 * each view hard-coded the comment bubble and the sentence "علّق على مقالك",
 * so an article-published notice was drawn as if it were a comment.
 *
 * Nothing new is stored: this reads the `data` JSON the notifications table
 * already holds.
 */
class NotificationPresenter
{
    /**
     * @param  array  $data  the notification's `data` payload
     * @return array{icon: string, tone: string, message: string, subject: ?string, url: ?string}
     */
    public static function make(array $data): array
    {
        $action = $data['action'] ?? null;
        $type = $data['type'] ?? null;

        return [
            'icon' => self::icon($data, $type, $action),
            'tone' => self::tone($action),
            'message' => self::message($data, $type, $action),
            'subject' => self::subject($data),
            'url' => $data['url'] ?? null,
        ];
    }

    /**
     * Icon NAME only — the Blade component owns the SVG paths, and they come
     * from the set the admin dashboard already uses. No new icon library.
     */
    private static function icon(array $data, ?string $type, ?string $action): string
    {
        return match (true) {
            // Anything to do with a comment keeps the speech bubble. The third
            // arm catches rows stored before `type` was part of the payload.
            $type === 'comment',
            Str::startsWith((string) $action, 'comment_'),
            $action === null && $type === null && isset($data['commenter_name']) => 'chat',

            // A newly published article is a document, not a conversation.
            $action === 'post_published' => 'document',
            $action === 'post_updated' => 'pencil',
            $action === 'post_deleted' => 'trash',

            // Account and role changes.
            $action === 'role_changed',
            Str::startsWith((string) $action, 'account_') => 'users',

            default => 'bell',
        };
    }

    /** Colour family for the icon bubble, matching the palette already in use. */
    private static function tone(?string $action): string
    {
        return match ($action) {
            'post_deleted', 'comment_deleted', 'account_deactivated' => 'red',
            'comment_hidden' => 'amber',
            default => 'brand',
        };
    }

    /**
     * The sentence itself.
     *
     * Moderation and publish notices carry a ready-made `message`; the comment
     * notification is composed from its parts, exactly as it always was.
     */
    private static function message(array $data, ?string $type, ?string $action): string
    {
        if (! empty($data['message'])) {
            return $data['message'];
        }

        // Comment payloads (including ones written before `type` was stored).
        if ($type === 'comment' || isset($data['commenter_name'])) {
            $who = $data['commenter_name'] ?? 'مستخدم';

            return "علّق {$who} على مقالك";
        }

        return 'لديك إشعار جديد.';
    }

    /** The article (or other subject) the notice is about, shown under it. */
    private static function subject(array $data): ?string
    {
        $subject = $data['post_title'] ?? $data['title'] ?? null;

        return $subject ? Str::limit($subject, 70) : null;
    }
}
