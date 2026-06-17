<?php

namespace App\Services;

use App\Mail\NotificationMail;
use App\Models\Forum;
use App\Models\ForumMessage;
use App\Models\Message;
use App\Models\Notice;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Central place for all outbound email notifications (direct messages,
 * forum replies, notices, …).
 *
 * Design guarantees:
 *   • Non-blocking — emails are sent in app()->terminating(), i.e. AFTER
 *     the HTTP response has been returned to the user, so sending a DM or
 *     posting in a forum never waits on SMTP.
 *   • Never breaks the action — every send is wrapped in try/catch; a mail
 *     failure is logged, not thrown back into the request.
 *   • Respects the org-wide switch — Settings → Notifications → Email
 *     Alerts (notifications_email_alerts) is a hard kill switch.
 *
 * Controllers call the small event helpers (newDirectMessage, …); the
 * recipient resolution and copy live here so the controllers stay thin.
 */
class Notifier
{
    /** Org-wide email kill switch (Settings → Notifications → Email Alerts). */
    public static function emailEnabled(): bool
    {
        return (bool) SystemSetting::getValue('notifications_email_alerts', true);
    }

    /**
     * Email one or many users after the response is sent. Never throws.
     *
     * @param  User|iterable<User>                 $recipients
     * @param  callable(User):?NotificationMail    $build  Builds the mail per recipient (return null to skip one).
     */
    public static function email($recipients, callable $build): void
    {
        if (!self::emailEnabled()) {
            return;
        }

        $targets = collect($recipients instanceof User ? [$recipients] : $recipients)
            ->filter(fn ($u) => $u instanceof User && filter_var($u->email ?? '', FILTER_VALIDATE_EMAIL))
            // Respect each user's personal "email notifications" preference
            // (defaults to true). In-app notifications are unaffected.
            ->filter(fn (User $u) => ($u->email_notifications ?? true))
            ->unique('id')
            ->values();

        if ($targets->isEmpty()) {
            return;
        }

        app()->terminating(function () use ($targets, $build) {
            foreach ($targets as $user) {
                try {
                    $mail = $build($user);
                    if ($mail instanceof NotificationMail) {
                        Mail::to($user->email)->send($mail);
                    }
                } catch (\Throwable $e) {
                    Log::warning("Notifier: email to {$user->email} failed: {$e->getMessage()}");
                }
            }
        });
    }

    /** First name for a friendly greeting. */
    private static function firstName(?string $name): ?string
    {
        $name = trim((string) $name);
        return $name !== '' ? strtok($name, ' ') : null;
    }

    /** Plain-text snippet from a possibly-rich body. */
    private static function snippet(?string $body, int $limit = 160): string
    {
        $text = trim(preg_replace('/\s+/', ' ', strip_tags((string) $body)));
        return Str::limit($text, $limit);
    }

    /** Absolute link into the frontend app. */
    private static function appUrl(string $path = ''): string
    {
        return rtrim((string) config('app.frontend_url'), '/') . $path;
    }

    /* ================================================================
     * Event helpers
     * ================================================================ */

    /**
     * A document reached an approver's stage — email the approver(s) that it
     * needs their action. Low-volume + actionable, so this is exactly the kind
     * of email worth sending even on a tight plan.
     */
    public static function approvalNeeded($recipients, string $docLabel, string $number, ?string $title, string $actionPath = '/procurement/pending-approvals'): void
    {
        self::email($recipients, fn (User $u) => new NotificationMail(
            subjectLine: "Approval needed: {$docLabel} {$number}",
            heading: "{$docLabel} {$number} needs your approval",
            lines: array_values(array_filter([
                $title ? "\u{201C}{$title}\u{201D}" : null,
                "This {$docLabel} has reached your approval step. Please review and approve, request changes, or reject it.",
            ])),
            actionText: 'Review & approve',
            actionUrl: self::appUrl($actionPath),
            greetingName: self::firstName($u->name),
            footnote: 'Sent because an approval is waiting on you in CASI 360.',
        ));
    }

    /**
     * A document reached a decision — email the requester/raiser the outcome.
     */
    public static function approvalDecision($recipients, string $docLabel, string $number, string $action, string $viewPath): void
    {
        $outcome = self::outcomeWord($action);

        self::email($recipients, fn (User $u) => new NotificationMail(
            subjectLine: "{$docLabel} {$number} was {$outcome}",
            heading: "{$docLabel} {$number} was {$outcome}",
            lines: ["Your {$docLabel} ({$number}) has been {$outcome}."],
            actionText: 'View details',
            actionUrl: self::appUrl($viewPath),
            greetingName: self::firstName($u->name),
            footnote: 'Update on a request you raised in CASI 360.',
        ));
    }

    private static function outcomeWord(string $action): string
    {
        return match ($action) {
            'approve', 'approved' => 'approved',
            'reject', 'rejected'  => 'rejected',
            'revision'            => 'sent back for revision',
            'forward'             => 'forwarded',
            default               => $action,
        };
    }

    /** A direct message was sent — notify the recipient. */
    public static function newDirectMessage(Message $message): void
    {
        $recipient = $message->recipient;
        $sender    = $message->sender;
        if (!$recipient || !$sender) {
            return;
        }

        self::email($recipient, fn (User $u) => new NotificationMail(
            subjectLine: "New message from {$sender->name}",
            heading: "New message from {$sender->name}",
            lines: array_values(array_filter([
                $message->subject ? "Subject: {$message->subject}" : null,
                self::snippet($message->body) ?: 'You have a new direct message.',
            ])),
            actionText: 'View message',
            actionUrl: self::appUrl('/communication/messages'),
            greetingName: self::firstName($u->name),
            footnote: "Sent because {$sender->name} messaged you on CASI 360.",
        ));
    }

    /**
     * A forum message was posted — notify the thread participants only.
     * A brand-new top-level post has no thread yet, so it notifies no one;
     * a reply notifies the author of the post it replied to plus everyone
     * else who has replied to that same post (minus the new poster).
     */
    public static function newForumReply(ForumMessage $message, ?Forum $forum = null): void
    {
        // Thread participants only: replies notify, new top-level posts don't.
        if (empty($message->reply_to_id)) {
            return;
        }

        $author = $message->user;
        if (!$author) {
            return;
        }

        $rootId = $message->reply_to_id;
        $participantIds = ForumMessage::query()
            ->where(fn ($q) => $q->where('id', $rootId)->orWhere('reply_to_id', $rootId))
            ->pluck('user_id')
            ->unique()
            ->reject(fn ($id) => $id === $author->id)
            ->values();

        if ($participantIds->isEmpty()) {
            return;
        }

        $forum     = $forum ?: $message->forum;
        $forumName = $forum?->name ?? 'a forum';
        $recipients = User::whereIn('id', $participantIds)->get();

        self::email($recipients, fn (User $u) => new NotificationMail(
            subjectLine: "New reply in {$forumName}",
            heading: "New reply from {$author->name}",
            lines: array_values(array_filter([
                "{$author->name} replied in the \"{$forumName}\" forum:",
                self::snippet($message->body) ?: 'A new reply was posted.',
            ])),
            actionText: 'View discussion',
            actionUrl: self::appUrl('/communication/forums'),
            greetingName: self::firstName($u->name),
            footnote: "You're part of this thread on CASI 360.",
        ));
    }

    /**
     * A notice was published — notify its targeted audience (all / by
     * department / by role). Drafts and archived notices send nothing.
     */
    public static function newNotice(Notice $notice): void
    {
        if (($notice->status ?? null) !== 'published') {
            return;
        }

        $notice->loadMissing('audiences', 'author');

        $recipients = self::usersForNoticeAudiences($notice->audiences)
            ->reject(fn (User $u) => $u->id === $notice->author_id)
            ->values();

        if ($recipients->isEmpty()) {
            return;
        }

        $author   = $notice->author;
        $priority = $notice->priority && $notice->priority !== 'normal'
            ? ucfirst($notice->priority) . ' notice'
            : 'Notice';

        self::email($recipients, fn (User $u) => new NotificationMail(
            subjectLine: "{$priority}: {$notice->title}",
            heading: $notice->title,
            lines: array_values(array_filter([
                self::snippet($notice->body, 280) ?: 'A new notice was posted.',
                $author ? "Posted by {$author->name}." : null,
            ])),
            actionText: 'Read notice',
            actionUrl: self::appUrl('/communication/notices'),
            greetingName: self::firstName($u->name),
            footnote: 'Organization notice from CASI 360.',
        ));
    }

    /**
     * Resolve the distinct users targeted by a set of notice audiences.
     * 'all' short-circuits to every user with an email; otherwise the
     * department and role targets are OR-ed together.
     *
     * @param  iterable  $audiences  NoticeAudience rows.
     * @return Collection<int,User>
     */
    private static function usersForNoticeAudiences($audiences): Collection
    {
        $audiences = collect($audiences);
        $base = User::query()->whereNotNull('email')->where('email', '!=', '');

        if ($audiences->contains(fn ($a) => $a->audience_type === 'all')) {
            return $base->get();
        }

        $deptIds = $audiences->where('audience_type', 'department')->pluck('audience_id')->filter()->unique()->values();
        $roles   = $audiences->where('audience_type', 'role')->pluck('audience_role')->filter()->unique()->values();

        if ($deptIds->isEmpty() && $roles->isEmpty()) {
            return collect();
        }

        return $base->where(function ($q) use ($deptIds, $roles) {
            if ($deptIds->isNotEmpty()) {
                $q->orWhereHas('employee', fn ($e) => $e->whereIn('department_id', $deptIds->all()));
            }
            if ($roles->isNotEmpty()) {
                $q->orWhereIn('role', $roles->all());
            }
        })->get();
    }
}
