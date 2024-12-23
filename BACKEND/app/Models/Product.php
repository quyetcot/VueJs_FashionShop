<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;
    protected $fillable = [
        "brand_id",
        "category_id",
        'type',
        'slug',
        'sku',
        'weight',
        'name',
        "views",
        'img_thumbnail',
        'price_regular',
        'price_sale',
        "quantity",
        'description',
        "description_title",
        "status",
        'is_show_home',
        'trend',
        'is_new',
    ];

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function comments()
    {
        return $this->hasMany(Comments::class);
    }
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function attributes()
    {
        return $this->belongsToMany(Attribute::class, "product_has_attributes", "product_id", "attribute_id")->withPivot("attribute_item_ids");
    }
    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }
    public function galleries()
    {
        return $this->hasMany(ProductGallery::class);
    }
    public function tags()
    {
        return $this->belongsToMany(Tag::class, "product_tags");
    }
    public function cartitems(){
        return $this->hasMany(CartItem::class);
    }
    public function orderDetails(){
        return $this->hasMany(OrderDetail::class);
    }
    protected $casts = [
        'type' => "boolean",
        "status" => "boolean",
        'is_show_home' => "boolean",
        'trend' => "boolean",
        'is_new' => "boolean",
        // "attribute_item_id"=>"json",
    ];
}
