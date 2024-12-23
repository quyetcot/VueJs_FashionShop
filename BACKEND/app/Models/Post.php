<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use HasFactory;

    protected $fillable = [
        'post_name',
        'post_content',
        'post_view',
        'slug',
        // 'description',
        'status',
        'user_id',
        'category_id',
        'featured',
        'img_thumbnail', // Lưu chuỗi JSON chứa nhiều hình ảnh
    ];

    public function getImgThumbnailAttribute($value)
    {
        return json_decode($value);
    }

    public function setImgThumbnailAttribute($value)
    {
        $this->attributes['img_thumbnail'] = json_encode($value);
    }
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

?>