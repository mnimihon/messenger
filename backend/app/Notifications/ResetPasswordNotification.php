<?php
namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPasswordNotification extends Notification
{

    private $code;

    public function __construct($code)
    {
        $this->code = $code;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Код для сброса пароля')
            ->greeting('Здравствуйте!')
            ->line('Вы запросили сброс пароля.')
            ->line('Ваш код для сброса пароля:')
            ->line('**' . $this->code . '**')
            ->line('Код действителен в течение 10 минут.')
            ->line('Если вы не запрашивали сброс пароля, проигнорируйте это письмо.');
    }
}
