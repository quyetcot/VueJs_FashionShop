<?php

namespace App\Jobs;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class UpdateOrderStatus implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $orders = Order::query()
            ->where('order_status', '=', Order::STATUS_SUCCESS)
            ->where('payment_status', '=', Order::PAYMENT_PAID)
            ->where('updated_at', '<=', now()->subDays(3))
            ->get();
        foreach ($orders as $order) {
            $order->update([
                'order_status' => Order::STATUS_COMPLETED,
            ]);
        }
    }
}
