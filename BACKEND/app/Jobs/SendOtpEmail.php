<?php

namespace App\Jobs;

use App\Mail\OtpEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SendOtpEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $email;
    protected $otp;
    /**
     * Create a new job instance.
     */
    public function __construct($email, $otp)
    {
        $this->email = $email;
        $this->otp = $otp;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Kiểm tra xem có thực sự nhận được email và otp không
        try {
            Mail::to($this->email)->send(new OtpEmail($this->otp));
        } catch (\Exception $e) {
            // In lỗi vào log nếu có sự cố
            Log::error('Email send failed: ' . $e->getMessage());
        }
    }
}
