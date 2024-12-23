<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    use HasFactory;
    protected $fillable = [
        "product_id",
        "price_regular",
        "price_sale",
        "quantity",
        "image",
        "sku",
        'id_guid'
        
    ];

    public function product(){
        return $this->belongsTo(Product::class);
    }
    public function attributes(){
        return $this->belongsToMany(Attribute::class,"product_variant_has_attributes")->withPivot("attribute_item_id",'value');
    }
    public function cartitems(){
        return $this->hasMany(CartItem::class);
    }
    public function orderDetails()
    {
        return $this->hasMany(OrderDetail::class);
    }
    
   
}
