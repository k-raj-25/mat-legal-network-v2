<?php

namespace App\Notifications;

use App\Models\LawyerProfile;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LawyerRegistrationReceived extends Notification implements ShouldQueue
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
            ->subject('We received your registration – '.config('mat.brand'))
            ->greeting('Hello '.$this->lawyer->full_name.',')
            ->line('Thank you for registering with '.config('mat.brand').'. Your profile (ID '.$this->lawyer->public_code.') is now with our team for verification.')
            ->line('Once it is approved, your profile will be visible to clients on our website. We will email you as soon as it is reviewed.')
            ->action('View my dashboard', route('lawyer.dashboard'));
    }
}
