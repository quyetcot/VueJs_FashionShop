<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();

        VerifyEmail::toMailUsing(function (object $notifiable, string $url) {
            return (new MailMessage)
            ->greeting('Mix-Match Xin Chào!')
            ->subject('Xác Thực Tài Khoản')
            ->line('Vui lòng xác thực tài khoản đăng ký của bạn.')
            ->action('Xác thực Email', $url)
            ->line('Cảm ơn bạn đã sử dụng dịch vụ của chúng tôi!')
            ->salutation('Trân trọng,')
            ->line('Mix_Match Fashion ');
        });
    }
}
