<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CartEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $cartId;
    public $cartItems;

    /**
     * Tạo một sự kiện mới.
     *
     * @param int $cartId
     * @param array $cartItems
     */
    public function __construct($cartId, $cartItems)
    {
        $this->cartId = $cartId;
        $this->cartItems = $cartItems;
        // dd($cartId, $cartItems);
    }

    /**
     * Xác định kênh mà sự kiện sẽ phát sóng.
     *
     * @return \Illuminate\Broadcasting\Channel
     */
    public function broadcastOn()
    {
        return new PrivateChannel('cart.' . $this->cartId);
    }
    public function broadcastAs()
{
    return 'CartEvent';  // Tên sự kiện phải trùng với frontend
}
}
