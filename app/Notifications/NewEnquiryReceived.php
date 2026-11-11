<?php

namespace App\Notifications;

use App\Models\Enquiry;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewEnquiryReceived extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Enquiry $enquiry) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $e = $this->enquiry;

        return (new MailMessage)
            ->subject('New client enquiry '.$e->reference)
            ->line('Name: '.$e->name)
            ->line('Phone: '.$e->phone.' (prefers '.$e->preferred_contact.')')
            ->line('Regarding: '.($e->lawyer?->full_name ?? 'General enquiry'))
            ->action('Open enquiry', route('admin.enquiries.show', $e));
    }
}
