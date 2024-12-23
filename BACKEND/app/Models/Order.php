<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'payment_method_id',
        'order_status',
        'payment_status',
        'order_code',
        'total_quantity',
        'total',
        'user_name',
        'user_email',
        'user_phonenumber',
        'user_address',
        'user_note',
        'ship_user_name',
        'ship_user_phonenumber',
        'ship_user_address',
        'voucher_id',
        'voucher_discount',
        'return_notes',
        'shipping_fee'
    ];
    // Order status constants
    const STATUS_PENDING = 'Đang chờ xác nhận';
    const STATUS_CONFIRMED = 'Đã xác nhận';
    const STATUS_CANCELED = 'Đã hủy';
    const STATUS_SHIPPING = 'Đang vận chuyển';
    const STATUS_SUCCESS = 'Giao hàng thành công';
    const STATUS_RETURNED = 'Hoàn trả hàng';
    const STATUS_COMPLETED = 'Hoàn thành';
    const STATUS_RETURN_REQUESTED = 'Yêu cầu hoàn trả hàng';

  
    // Payment status constants
    const PAYMENT_PENDING = 'Chưa Thanh Toán';
    const PAYMENT_PAID = 'Đã thanh toán';
    // Get all order statuses
    public static function getOrderStatuses()
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_CONFIRMED,
            self::STATUS_CANCELED,
            self::STATUS_SHIPPING,
            self::STATUS_SUCCESS,
            self::STATUS_RETURNED,
            self::STATUS_COMPLETED,
            self::STATUS_RETURN_REQUESTED
        ];
    }

    // Get all payment statuses
    public static function getPaymentStatuses()
    {
        return [
            self::PAYMENT_PENDING,
            self::PAYMENT_PAID,
        ];
    }
    protected static function boot()
    {
        parent::boot();
        static::creating(function ($order) {
            // Tạo mã order_code bao gồm chữ và số
            $order->order_code = 'MIXMATCH-' . strtoupper(uniqid());
        });
    }
    // Quan hệ với model User (người dùng)
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Quan hệ với model PaymentMethod (phương thức thanh toán)
    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    // Quan hệ với model OrderDetail (chi tiết đơn hàng)
    public function orderDetails()
    {
        return $this->hasMany(OrderDetail::class);
    }
    // Liên kết đến voucher_logs
    public function voucherLogs()
    {
        return $this->hasMany(VoucherLog::class);
    }
    public function returnRequests(){
        return $this->hasMany(ReturnRequest::class);
    }
    
}