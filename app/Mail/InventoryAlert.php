<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InventoryAlert extends Mailable
{
    use SerializesModels;

    public function __construct(
        public array $criticalProducts,
        public array $lowProducts,
        public array $summary
    ) {}

    public function envelope(): Envelope
    {
        $criticalCount = count($this->criticalProducts);
        $lowCount = count($this->lowProducts);

        $subject = '🚨 Inventory Alert';
        if ($criticalCount > 0) {
            $subject .= " - {$criticalCount} Critical Stock Items";
        }
        if ($lowCount > 0) {
            $subject .= " - {$lowCount} Low Stock Items";
        }

        return new Envelope(
            subject: $subject
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.inventory-alert',
            with: [
                'criticalProducts' => $this->criticalProducts,
                'lowProducts' => $this->lowProducts,
                'summary' => $this->summary,
            ]
        );
    }
}
