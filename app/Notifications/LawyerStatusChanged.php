<?php

namespace App\Notifications;

use App\Enums\LawyerStatus;
use App\Models\LawyerProfile;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LawyerStatusChanged extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public LawyerProfile $lawyer) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $m = (new MailMessage)->greeting('Hello '.$this->lawyer->full_name.',');

        return match ($this->lawyer->status) {
            LawyerStatus::Approved => $m->subject('Your profile is live on '.config('mat.brand'))
                ->line('Good news – your profile has been verified and approved. Clients can now find you on our website.')
                ->action('View my public profile', route('lawyers.show', $this->lawyer)),
            LawyerStatus::Rejected => $m->subject('Action needed on your '.config('mat.brand').' registration')
                ->line('Our team reviewed your registration and needs a few changes before it can be approved:')
                ->line('“'.$this->lawyer->status_reason.'”')
                ->action('Update and resubmit', route('lawyer.profile.edit')),
            LawyerStatus::Suspended => $m->subject('Your '.config('mat.brand').' profile has been suspended')
                ->line('Your profile is temporarily hidden from the website for the following reason:')
                ->line('“'.$this->lawyer->status_reason.'”')
                ->line('Please contact our support team if you believe this is a mistake.'),
            default => $m->subject('Your profile status has changed')->line('Your profile status is now: '.$this->lawyer->status->label()),
        };
    }
}
