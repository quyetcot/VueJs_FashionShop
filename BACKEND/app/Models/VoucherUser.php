<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VoucherUser extends Model
{
    use HasFactory;

    protected $fillable = [
        'voucher_id',
        'user_id',
        'times_used',
    ];

    // Liên kết đến voucher
    public function voucher()
    {
        return $this->belongsTo(Voucher::class);
    }

    // Liên kết đến user
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
