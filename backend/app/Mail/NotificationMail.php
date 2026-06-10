<?php

namespace App\Mail;

use App\Models\SystemSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * One branded notification email used across the whole platform — direct
 * messages, forum replies, notices, and ad-hoc system alerts. Keeping a
 * single Mailable + template means every email looks consistent and we
 * never hand-roll HTML at the call site.
 *
 * Construct it with a subject, a heading, and the body paragraphs; add an
 * optional call-to-action button (text + url) that deep-links into the
 * frontend app.
 */
class NotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  string       $subjectLine   Email subject.
     * @param  string       $heading       Big heading inside the card.
     * @param  string[]     $lines         Body paragraphs (plain text, auto-escaped).
     * @param  string|null  $actionText    CTA button label (optional).
     * @param  string|null  $actionUrl     CTA button URL (optional, frontend link).
     * @param  string|null  $greetingName  "Hi {name}," line (optional).
     * @param  string|null  $footnote      Small grey note above the footer (optional).
     */
    public function __construct(
        public string $subjectLine,
        public string $heading,
        public array $lines = [],
        public ?string $actionText = null,
        public ?string $actionUrl = null,
        public ?string $greetingName = null,
        public ?string $footnote = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->subjectLine);
    }

    public function content(): Content
    {
        $orgName = SystemSetting::getValue('organization_name') ?: config('mail.from.name', 'CASI 360');

        return new Content(
            view: 'emails.notification',
            with: [
                'orgName'      => $orgName,
                'heading'      => $this->heading,
                'lines'        => $this->lines,
                'actionText'   => $this->actionText,
                'actionUrl'    => $this->actionUrl,
                'greetingName' => $this->greetingName,
                'footnote'     => $this->footnote,
            ],
        );
    }
}
