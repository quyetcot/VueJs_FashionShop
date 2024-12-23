<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Comments extends Model
{
    use HasFactory;
    protected $fillable = [
        "user_id",
        "product_id",
        "content",
        "rating",
        'image',
        "status",
         "parent_id",
         'order_id',
    ];

    public function product(){
        return $this->belongsTo(Product::class);
    }
    

    public function user()
    {
        return $this->belongsTo(User::class)->select('id', 'name', 'avatar');
    }

    public function parent()
    {
        return $this->belongsTo(Comments::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Comments::class, 'parent_id');
    }
    public function childrenRecursive()
    {
        return $this->hasMany(Comments::class, 'parent_id')->with('user:id,name,avatar', 'childrenRecursive');
    }
}
    