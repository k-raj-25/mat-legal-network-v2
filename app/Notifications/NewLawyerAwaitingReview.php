<?php

namespace App\Notifications;

use App\Models\LawyerProfile;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewLawyerAwaitingReview extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public LawyerProfile $lawyer) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New lawyer registration awaiting review: '.$this->lawyer->full_name)
            ->line($this->lawyer->full_name.' ('.$this->lawyer->public_code.') has registered and is waiting for approval.')
            ->action('Review registration', route('admin.lawyers.show', $this->lawyer));
    }
}
