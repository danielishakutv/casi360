<?php

namespace App\Console\Commands;

use App\Mail\NotificationMail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * Send a branded test email to confirm SMTP (ZeptoMail) is wired up before
 * any live notification depends on it.
 *
 *   php artisan mail:test you@example.com
 *   php artisan mail:test you@example.com --name="Daniel"
 *
 * Prints the mailer/host it used and the exact error on failure, so a
 * misconfigured token or unverified sender is obvious immediately.
 */
class MailTestCommand extends Command
{
    protected $signature = 'mail:test {email : Where to send the test} {--name= : Optional greeting name}';

    protected $description = 'Send a branded test email to verify the SMTP (ZeptoMail) configuration works.';

    public function handle(): int
    {
        $email = $this->argument('email');

        $this->newLine();
        $this->line('  Mailer : ' . config('mail.default'));
        $this->line('  Host   : ' . config('mail.mailers.smtp.host') . ':' . config('mail.mailers.smtp.port'));
        $this->line('  From   : ' . config('mail.from.name') . ' <' . config('mail.from.address') . '>');
        $this->line('  To     : ' . $email);
        $this->newLine();
        $this->info('Sending…');

        try {
            Mail::to($email)->send(new NotificationMail(
                subjectLine: 'CASI 360 email test',
                heading: 'Your email is working',
                lines: [
                    'This is a test message from CASI 360 confirming that outbound email (ZeptoMail) is configured correctly.',
                    'If you can read this in your inbox, notifications, messages, and forum emails are ready to go.',
                ],
                actionText: 'Open CASI 360',
                actionUrl: config('app.frontend_url'),
                greetingName: $this->option('name') ?: null,
                footnote: 'Sent by the mail:test console command.',
            ));

            $this->newLine();
            $this->info('✓ Sent without error. Check the inbox (and the spam folder just in case).');
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->newLine();
            $this->error('✗ Send failed: ' . $e->getMessage());
            $this->line('Common causes: wrong MAIL_PASSWORD (Send token), MAIL_USERNAME not "emailapikey",');
            $this->line('an unverified MAIL_FROM_ADDRESS domain in ZeptoMail, or the wrong region host.');
            return self::FAILURE;
        }
    }
}
