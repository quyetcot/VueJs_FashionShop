<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VoucherLog extends Model
{
    use HasFactory;
    protected $fillable = [
        'voucher_id',
        'user_id',
        'order_id',
        'action',
    ];

    // Liên kết với bảng Voucher
    public function voucher()
    {
        return $this->belongsTo(Voucher::class);
    }

    // Liên kết với bảng User
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Liên kết với bảng Order
    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
