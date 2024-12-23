<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttributeItem extends Model
{
    use HasFactory;
    protected $fillable = [
        "attribute_id",
        "value",
        "slug"

    ];

    public function attribute()
    {
        return $this->belongsTo(Attribute::class);
    }

    public function productvariants(){
        return $this->belongsToMany(ProductVariant::class,"product_variant_has_attributes","attribute_item_id",'product_variant_id')->withPivot('attribute_id','value');
    }
}
