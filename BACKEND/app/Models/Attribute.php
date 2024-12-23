<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attribute extends Model
{
    use HasFactory;
    protected $fillable = ["name", "slug"];
    public function attributeitems()
    {
        return $this->hasMany(AttributeItem::class);
    }
    public function producs()
    {
        return $this->belongsToMany(Product::class, "product_has_attributes")->withPivot('attribute_item_ids');
    }
    public function productvariants()
    {
        return $this->belongsToMany(ProductVariant::class, "product_variant_has_attributes")->withPivot('attribute_item_id','value');
    }
    
    
}
