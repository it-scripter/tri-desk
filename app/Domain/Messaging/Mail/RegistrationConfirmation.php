<?php

namespace App\Domain\Messaging\Mail;

use App\Domain\Competition\Enums\RegistrationStatus;
use App\Domain\Competition\Models\Registration;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Confirmation of a registration with the magic link (specification W-15).
 * The text is fixed for now; phase 3 moves it into the template engine
 * (H-40 to H-44).
 *
 * Sent through the database queue, and only once the registration is
 * committed. The job is encrypted: it carries the plain magic link token,
 * which must not sit readable in the jobs table until the cron run sends it.
 */
class RegistrationConfirmation extends Mailable implements ShouldBeEncrypted, ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Registration $registration,
        public readonly string $token,
    ) {
        $this->afterCommit();
    }

    public function envelope(): Envelope
    {
        $race = $this->registration->race;

        return new Envelope(
            subject: __('registration_form.mail.subject', [
                'race' => $race->name,
                'edition' => $race->edition->titleWithNumber(),
            ]),
        );
    }

    public function content(): Content
    {
        $race = $this->registration->race;

        return new Content(
            view: 'mail.registration-confirmation',
            text: 'mail.registration-confirmation-text',
            with: [
                'registration' => $this->registration->loadMissing(['participants', 'options.option', 'options.variant']),
                'race' => $race,
                'edition' => $race->edition,
                'reserved' => $this->registration->status === RegistrationStatus::Reserved,
                // Built here rather than in a request: the queued job has no
                // competition context, so the slug is passed explicitly.
                'link' => route('competition.registration.manage', [
                    'competition' => $race->edition->competition->slug,
                    'token' => $this->token,
                ]),
            ],
        );
    }
}
