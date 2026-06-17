<?php

namespace App\Services;

use App\Models\Boq;
use App\Models\BoqAuditLog;
use App\Models\Department;
use App\Models\Forum;
use App\Models\ForumMessage;
use App\Models\Notice;
use App\Models\Requisition;
use App\Models\Rfp;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Unified IN-APP notification system (the top-bar bell).
 *
 * Creates one `notifications` row per recipient for org events — forum
 * activity, notices, and approval requests/decisions — so every user has their
 * own read state. Direct messages are intentionally NOT duplicated here (they
 * have their own read tracking + the dedicated Messages badge).
 *
 * Every public helper is wrapped so a notification failure is logged and never
 * breaks the underlying action (posting, approving, …). Email delivery
 * (Zeptomail) is handled separately by {@see Notifier} and plugs into the same
 * events later.
 */
class NotificationService
{
    /* ================================================================
     * Core
     * ================================================================ */

    /**
     * Insert in-app notifications for the given recipients.
     *
     * @param  iterable  $users        User models (or ids); nulls are skipped.
     * @param  string    $excludeId    A user id to skip (usually the actor).
     */
    public static function push(
        iterable $users,
        string $type,
        string $title,
        ?string $body = null,
        ?string $url = null,
        ?array $data = null,
        ?string $excludeId = null
    ): void {
        $now  = now();
        $rows = [];
        $seen = [];

        foreach ($users as $u) {
            if ($u === null) {
                continue;
            }
            $id = $u instanceof User ? $u->id : (is_string($u) ? $u : ($u->id ?? null));
            if (!$id || $id === $excludeId || isset($seen[$id])) {
                continue;
            }
            $seen[$id] = true;

            $rows[] = [
                'id'         => (string) Str::uuid(),
                'user_id'    => $id,
                'type'       => $type,
                'title'      => Str::limit($title, 250, ''),
                'body'       => $body !== null ? Str::limit($body, 480, '') : null,
                'url'        => $url,
                'data'       => $data ? json_encode($data) : null,
                'read_at'    => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (!$rows) {
            return;
        }

        foreach (array_chunk($rows, 200) as $chunk) {
            DB::table('notifications')->insert($chunk);
        }
    }

    /* ================================================================
     * Communication events
     * ================================================================ */

    /** A forum message was posted. New post → whole forum; reply → thread. */
    public static function forumMessage(ForumMessage $message, ?Forum $forum = null): void
    {
        self::guard(function () use ($message, $forum) {
            $forum = $forum ?: $message->forum;
            if (!$forum) {
                return;
            }

            $author     = $message->user;
            $authorName = $author?->name ?? 'Someone';
            $isReply    = !empty($message->reply_to_id);

            if ($isReply) {
                // Notify the people in this thread (root author + co-repliers).
                $rootId = $message->reply_to_id;
                $ids = ForumMessage::query()
                    ->where(fn ($q) => $q->where('id', $rootId)->orWhere('reply_to_id', $rootId))
                    ->pluck('user_id')->unique()->values();
                $recipients = User::whereIn('id', $ids)->get();
                $title = "{$authorName} replied in {$forum->name}";
            } else {
                // Notify everyone who can see the forum.
                $recipients = self::forumAudience($forum);
                $title = "{$authorName} posted in {$forum->name}";
            }

            self::push(
                $recipients,
                'forum',
                $title,
                self::snippet($message->body),
                '/communication/forums',
                ['forum_id' => $forum->id, 'message_id' => $message->id],
                $author?->id
            );
        });
    }

    /** A notice was published — notify its targeted audience. */
    public static function notice(Notice $notice): void
    {
        self::guard(function () use ($notice) {
            if (($notice->status ?? null) !== 'published') {
                return;
            }
            $notice->loadMissing('audiences');

            $recipients = self::usersForNoticeAudiences($notice->audiences);
            $label = $notice->priority && $notice->priority !== 'normal'
                ? ucfirst($notice->priority) . ' notice'
                : 'Notice';

            self::push(
                $recipients,
                'notice',
                "{$label}: {$notice->title}",
                self::snippet($notice->body, 200),
                '/communication/notices',
                ['notice_id' => $notice->id],
                $notice->author_id
            );
        });
    }

    /* ================================================================
     * Procurement approval events
     * ================================================================ */

    /** A PR approval stage is now pending — notify whoever can act on it. */
    public static function requisitionPending(Requisition $req, string $stage): void
    {
        self::guard(function () use ($req, $stage) {
            $recipients = match ($stage) {
                'budget_holder' => array_filter([self::budgetHolderUser($req)]),
                'finance'       => self::managersInDepartment('FINANCE')->all(),
                'procurement'   => self::managersInDepartment('PROCUREMENT')->all(),
                'operations'    => self::managersInDepartment('OPERATIONS')->all(),
                default         => [],
            };
            if (!$recipients) {
                return;
            }

            self::push(
                $recipients,
                'approval',
                "Purchase Request {$req->requisition_number} needs your approval",
                $req->title ? Str::limit($req->title, 120, '') : null,
                '/procurement/pending-approvals',
                ['requisition_id' => $req->id, 'stage' => $stage]
            );

            Notifier::approvalNeeded($recipients, 'Purchase Request', $req->requisition_number, $req->title, '/procurement/pending-approvals');
        });
    }

    /** A PR reached a final decision — notify the requester. */
    public static function requisitionDecided(Requisition $req, string $action): void
    {
        self::guard(function () use ($req, $action) {
            $recipients = array_filter([
                $req->requested_by ? User::find($req->requested_by) : null,
                ($req->submitted_by && $req->submitted_by !== $req->requested_by) ? User::find($req->submitted_by) : null,
            ]);
            if (!$recipients) {
                return;
            }

            self::push(
                $recipients,
                'requisition',
                "Purchase Request {$req->requisition_number} was " . self::verb($action),
                $req->title ? Str::limit($req->title, 120, '') : null,
                '/procurement/purchase-requests',
                ['requisition_id' => $req->id, 'action' => $action]
            );

            Notifier::approvalDecision($recipients, 'Purchase Request', $req->requisition_number, $action, '/procurement/purchase-requests');
        });
    }

    /** A BOQ was submitted — notify the Operations approvers (legacy single-stage). */
    public static function boqSubmitted(Boq $boq): void
    {
        self::boqPending($boq, 'operations');
    }

    /**
     * A BOQ approval stage is now pending — notify whoever can act on it.
     * Mirrors the PR chain (Budget Holder → Finance → Procurement → Operations).
     */
    public static function boqPending(Boq $boq, string $stage): void
    {
        self::guard(function () use ($boq, $stage) {
            $recipients = match ($stage) {
                'budget_holder' => array_filter([self::boqBudgetHolderUser($boq)]),
                'finance'       => self::managersInDepartment('FINANCE')->all(),
                'procurement'   => self::managersInDepartment('PROCUREMENT')->all(),
                'operations'    => self::managersInDepartment('OPERATIONS')->all(),
                default         => [],
            };
            if (!$recipients) {
                return;
            }

            self::push(
                $recipients,
                'approval',
                "BOQ {$boq->boq_number} needs your approval",
                $boq->title ? Str::limit($boq->title, 120, '') : null,
                '/procurement/pending-approvals',
                ['boq_id' => $boq->id, 'stage' => $stage]
            );

            Notifier::approvalNeeded($recipients, 'BOQ', $boq->boq_number, $boq->title, '/procurement/pending-approvals');
        });
    }

    /** A BOQ was decided — notify whoever created it. */
    public static function boqDecided(Boq $boq, string $action): void
    {
        self::guard(function () use ($boq, $action) {
            $creatorId = BoqAuditLog::where('boq_id', $boq->id)
                ->where('action', 'created')
                ->orderBy('created_at')
                ->value('actor_id');
            $creator = $creatorId ? User::find($creatorId) : null;
            if (!$creator) {
                return;
            }

            self::push(
                [$creator],
                'boq',
                "BOQ {$boq->boq_number} was " . self::verb($action),
                $boq->title ? Str::limit($boq->title, 120, '') : null,
                '/procurement/boq',
                ['boq_id' => $boq->id, 'action' => $action]
            );

            Notifier::approvalDecision([$creator], 'BOQ', $boq->boq_number, $action, '/procurement/boq');
        });
    }

    /* ================================================================
     * Payment Request (RFP) approval events — v2 §3.3
     * ================================================================ */

    /** An RFP approval stage is now pending — notify whoever can act on it. */
    public static function rfpPending(Rfp $rfp, string $stage): void
    {
        self::guard(function () use ($rfp, $stage) {
            $recipients = match ($stage) {
                'programme_manager' => self::managersInDepartment('PROGRAMS')->all(),
                'finance'           => self::managersInDepartment('FINANCE')->all(),
                'final_approver'    => self::paymentFinalApprovers()->all(),
                default             => [],
            };
            if (!$recipients) {
                return;
            }

            self::push(
                $recipients,
                'approval',
                "Payment Request {$rfp->rfp_number} needs your approval",
                $rfp->payee ? Str::limit("Payee: {$rfp->payee}", 120, '') : null,
                '/procurement/pending-approvals',
                ['rfp_id' => $rfp->id, 'stage' => $stage]
            );

            Notifier::approvalNeeded($recipients, 'Payment Request', $rfp->rfp_number, $rfp->payee, '/procurement/pending-approvals');
        });
    }

    /** An RFP reached a decision — notify whoever raised it. */
    public static function rfpDecided(Rfp $rfp, string $action): void
    {
        self::guard(function () use ($rfp, $action) {
            $raiser = $rfp->raised_by ? User::find($rfp->raised_by) : null;
            if (!$raiser) {
                return;
            }

            self::push(
                [$raiser],
                'rfp',
                "Payment Request {$rfp->rfp_number} was " . self::verb($action),
                $rfp->payee ? Str::limit("Payee: {$rfp->payee}", 120, '') : null,
                '/procurement/rfp',
                ['rfp_id' => $rfp->id, 'action' => $action]
            );

            Notifier::approvalDecision([$raiser], 'Payment Request', $rfp->rfp_number, $action, '/procurement/rfp');
        });
    }

    /* ================================================================
     * Recipient resolvers
     * ================================================================ */

    /** Everyone who can see a forum: department members for a department
     *  forum, otherwise all active users. */
    private static function forumAudience(Forum $forum): Collection
    {
        $base = User::query()->where('status', 'active');

        if (($forum->type ?? null) === 'department' && $forum->department_id) {
            return $base->whereHas('employee', fn ($e) => $e->where('department_id', $forum->department_id))->get();
        }

        return $base->get();
    }

    /** Active managers whose department matches the given code (by code or name). */
    private static function managersInDepartment(string $code): Collection
    {
        $code = strtoupper(trim($code));
        $dept = Department::where('code', $code)->first();
        $names = array_values(array_filter([$code, $dept?->name]));

        return User::where('role', 'manager')
            ->where('status', 'active')
            ->whereIn('department', $names)
            ->get();
    }

    /** The user account of a PR's budget holder (matched by email), if any. */
    private static function budgetHolderUser(Requisition $req): ?User
    {
        $req->loadMissing('budgetHolder');
        $holder = $req->budgetHolder;
        if (!$holder) {
            return null;
        }
        if ($holder->user_id) {
            $u = User::find($holder->user_id);
            if ($u) {
                return $u;
            }
        }
        return $holder->email ? User::where('email', $holder->email)->first() : null;
    }

    /** The user account of a BOQ's budget holder (matched by email), if any. */
    private static function boqBudgetHolderUser(Boq $boq): ?User
    {
        $boq->loadMissing('budgetHolder');
        $holder = $boq->budgetHolder;
        if (!$holder) {
            return null;
        }
        if ($holder->user_id) {
            $u = User::find($holder->user_id);
            if ($u) {
                return $u;
            }
        }
        return $holder->email ? User::where('email', $holder->email)->first() : null;
    }

    /**
     * The designated Payment-Request final approver(s): Country Director by
     * role (default) or Operations managers, per config('procurement.payment_final_approver').
     */
    private static function paymentFinalApprovers(): Collection
    {
        $role = config('procurement.payment_final_approver', 'country_director');

        if ($role === 'operations') {
            return self::managersInDepartment('OPERATIONS');
        }

        return User::where('role', 'country_director')->where('status', 'active')->get();
    }

    /** Distinct users for a notice's audiences (mirrors Notifier). */
    private static function usersForNoticeAudiences($audiences): Collection
    {
        $audiences = collect($audiences);
        $base = User::query()->where('status', 'active');

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

    /* ================================================================
     * Helpers
     * ================================================================ */

    private static function verb(string $action): string
    {
        return match ($action) {
            'approve', 'approved' => 'approved',
            'reject', 'rejected'  => 'rejected',
            'revision'            => 'sent back for revision',
            'forward'             => 'forwarded',
            default               => $action,
        };
    }

    private static function snippet(?string $body, int $limit = 160): string
    {
        $text = trim(preg_replace('/\s+/', ' ', strip_tags((string) $body)));
        return Str::limit($text, $limit, '');
    }

    /** Run a notification block; log and swallow any failure. */
    private static function guard(callable $fn): void
    {
        try {
            $fn();
        } catch (\Throwable $e) {
            Log::warning('NotificationService: ' . $e->getMessage());
        }
    }
}
