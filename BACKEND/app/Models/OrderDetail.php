<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderDetail extends Model
{
    use HasFactory;
    protected $fillable = [
        'product_id',
        'product_variant_id',
        'order_id',
        'product_name',
        'product_img',
        'attributes',
        'quantity',
        'price',
        'total_price',
        'discount'
    ];

    // Quan hệ với model Product (sản phẩm)
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // Quan hệ với model ProductVariant (biến thể sản phẩm)
    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class);
    }

    // Quan hệ với model Order (đơn hàng)
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    // Lấy các thuộc tính (attributes) của sản phẩm từ dạng JSON
    public function getAttributesAttribute($value)
    {
        return json_decode($value, true);
    }

    // Gán các thuộc tính (attributes) của sản phẩm ở dạng JSON
    public function setAttributesAttribute($value)
    {
        $this->attributes['attributes'] = empty($value) ? null : json_encode($value);
    }
}
