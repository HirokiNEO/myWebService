<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProvisionalRegisterNotification extends Notification
{
    use Queueable;

    public function __construct(public string $verificationUrl) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('【MyWebService】本登録手続きのご案内')
            ->greeting('仮登録ありがとうございます。')
            ->line('以下のボタンをクリックして、アカウント名およびパスワードを設定し、本登録を完了してください。')
            ->action('本登録を完了する', $this->verificationUrl)
            ->line('※このURLの有効期限は送信から60分間です。')
            ->line('※お心当たりのない場合は、このメールを破棄してください。');
    }
}
