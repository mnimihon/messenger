<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmailVerificationCodeNotification extends Notification
{
    use Queueable;

    private int $verificationCode;

    public function __construct(int $verificationCode)
    {
        $this->verificationCode = $verificationCode;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Подтверждение email')
            ->line('Ваш код подтверждения: ' . $this->verificationCode)
            ->line('Код действителен в течение 10 минут.')
            ->action('Подтвердить email', url('/verify-email'))
            ->line('Спасибо за регистрацию!');
    }
}
