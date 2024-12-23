<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReturnItem extends Model
{
    use HasFactory;
    protected $fillable=[
        "return_request_id",
        "order_detail_id",
        "quantity",
        "image",
        "status",
        "refund_amount"
    ];
    public function returnRequest(){
        return $this->belongsTo(ReturnRequest::class);
    }
    public function OrderDetail(){
        return $this->belongsTo(OrderDetail::class);
    }
}
