<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordChangedNotification extends Notification
{
    use Queueable;

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('Your AVIA password was changed')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('The password for your AVIA account was changed.')
            ->line('All previous web sessions and mobile access tokens have been signed out.')
            ->line('If you did not make this change, contact your administrator immediately.')
            ->action('Sign in to AVIA', route('login'));
    }
}
