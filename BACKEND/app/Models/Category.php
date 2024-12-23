<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;
    protected $fillable = [
        "name",
        'slug',
        'description',
        'img_thumbnail',
        'parent_id',
    ];


    // Quan hệ với danh mục con
    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    // Quan hệ với danh mục cha
    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }
    public function products(){
        return $this->hasMany(Product::class);
    }
    // Lấy tất cả danh mục con (bao gồm cả con cháu)
    public function allChildren()
     {
         return $this->children()->with('allChildren');
     }
    public function posts()
    {
        return $this->hasMany(Post::class);
    }
}
