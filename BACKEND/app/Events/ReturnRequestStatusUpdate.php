<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReturnRequestStatusUpdate
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public $request;

    public function __construct($request)
    {
        $this->request = $request;
    }

    public function broadcastOn()
    {
        return new Channel('request'); // Kênh truyền dữ liệu
    }

    public function broadcastAs()
    {
        return 'request.updated'; // Tên sự kiện
    }
}
