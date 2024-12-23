<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReturnLog extends Model
{
    use HasFactory;
    protected $fillable=[
        "return_request_id",
        "user_id",
        "action",
        "comment",
    ];
    public function returnRequest(){
        return $this->belongsTo(ReturnRequest::class);
    }
    public function user(){
        return $this->belongsTo(User::class);
    }
}
