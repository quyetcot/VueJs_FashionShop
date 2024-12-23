<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;
use Illuminate\Auth\Notifications\VerifyEmail as BaseVerifyEmail;
use Illuminate\Routing\Route;

class CustomVerifyEmail extends BaseVerifyEmail
{

    protected function verificationUrl($notifiable)
    {
        // Tạo URL từ APP_URL thay vì localhost
        return URL::temporarySignedRoute(
            'verification.verify', // tên route xác thực
            Carbon::now()->addMinutes(60), // thời gian hết hạn
            [
                'id'       => $notifiable->getKey(), 
                'hash'     => sha1($notifiable->getEmailForVerification()),

            ]
        );
    }

//     protected function verificationUrl($notifiable)
// {
//     // Địa chỉ IP cố định mà bạn muốn sử dụng
//     $fixedIp = 'http://127.0.0.1:8000';

//     // Tạo URL tạm thời cho xác thực email
//     return $fixedIp . '/' . route('verification.verify', [
//         'id' => $notifiable->getKey(),
//         'hash' => sha1($notifiable->getEmailForVerification()),
//         'expires' => Carbon::now()->addMinutes(60)->timestamp,
//         'signature' => hash_hmac('sha256', $notifiable->getKey(), config('app.key')), // Tạo chữ ký cho URL
//     ]);
// }
}
