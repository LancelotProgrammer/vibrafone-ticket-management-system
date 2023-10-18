<?php

namespace App\Mail;

use App\Enums\EmailType;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TicketEscalation extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        private readonly EmailType $type,
        private readonly string $title,
    ) {
        //
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        if ($this->type == EmailType::ADMIN) {
            return new Envelope(
                subject: $this->title,
            );
        }
        if ($this->type == EmailType::CUSTOMER) {
            return new Envelope(
                subject: $this->title,
            );
        }
        if ($this->type == EmailType::HIGH_TECHNICAL_SUPPORT) {
            return new Envelope(
                subject: $this->title,
            );
        }
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        if ($this->type == EmailType::ADMIN) {
            return new Content(
                markdown: 'emails.ticket.escalation',
                with: [
                    'body' => 'Ticket escalation ADMIN',
                ],
            );
        }
        if ($this->type == EmailType::CUSTOMER) {
            return new Content(
                markdown: 'emails.ticket.escalation',
                with: [
                    'body' => 'Ticket escalation CUSTOMER',
                ],
            );
        }
        if ($this->type == EmailType::HIGH_TECHNICAL_SUPPORT) {
            return new Content(
                markdown: 'emails.ticket.escalation',
                with: [
                    'body' => 'Ticket escalation HIGH_TECHNICAL_SUPPORT',
                ],
            );
        }
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
