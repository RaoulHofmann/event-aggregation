<?php

namespace App\Mail;

use App\Support\PublicSite;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class PendingRegistrationMail extends Mailable
{
    public function __construct(public string $applicantName, public string $applicantEmail) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'New EventJunction registration');
    }

    public function content(): Content
    {
        return new Content(view: 'mail.pending-registration', with: [
            'reviewUrl' => PublicSite::url('admin?status=pending'),
        ]);
    }
}
