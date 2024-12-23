<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $url;
    protected $token;

    public function __construct($url, $token)
    {
        $this->url = $url;
        $this->token = $token;
    }

    public function via($notifiable)
    {
        return ['mail']; // Gửi qua email
    }

    public function toMail($notifiable)
{
    return (new MailMessage)
        ->subject('Yêu cầu đặt lại mật khẩu từ hệ thống của chúng tôi')
        ->greeting('Xin chào!')
        ->line('Chúng tôi nhận được yêu cầu đặt lại mật khẩu cho tài khoản của bạn.')
        ->action('Đặt lại mật khẩu', $this->url)
        ->line('Nếu bạn không yêu cầu đặt lại mật khẩu, bạn có thể bỏ qua email này.')
        ->salutation('Trân trọng, Đội ngũ Hỗ trợ');
}
}