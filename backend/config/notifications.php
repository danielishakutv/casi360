<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Direct-message email throttling + presence
    |--------------------------------------------------------------------------
    |
    | Direct-message (DM) emails are deliberately NOT sent on every message.
    | A DM email goes out only when BOTH are true:
    |   1. We haven't emailed this recipient about a message in the last
    |      `message_email_throttle_minutes` minutes, AND
    |   2. The recipient is NOT actively using the dashboard (their session
    |      had no activity within `active_window_seconds`) or is logged out.
    |
    | This keeps a busy conversation from spamming someone's inbox while they
    | are away, and never emails someone who is already looking at the app.
    |
    | NOTE: This only affects DIRECT MESSAGES. Approval, notice, and forum-reply
    | emails are unaffected (they are already targeted + low-volume).
    |
    | All three are tunable from .env without a redeploy.
    |
    */

    // Minimum minutes between message-emails to the same recipient.
    'message_email_throttle_minutes' => (int) env('MESSAGE_EMAIL_THROTTLE_MINUTES', 30),

    // A user counts as "actively using the dashboard" if a session of theirs
    // had request activity within this many seconds. The SPA polls every few
    // seconds while open, so a fresh value = looking now; stale = away.
    'active_window_seconds' => (int) env('PRESENCE_ACTIVE_WINDOW_SECONDS', 180),

    // When false, message emails ignore presence (still throttled by the
    // window above). Set to false to email away-or-not, just rate-limited.
    'message_email_presence_aware' => (bool) env('MESSAGE_EMAIL_PRESENCE_AWARE', true),

];
