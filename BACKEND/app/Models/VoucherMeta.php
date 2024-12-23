<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VoucherMeta extends Model
{
    protected $fillable = [
        'voucher_id',
        'meta_key',
        'meta_value',
    ];

    // Liên kết đến voucher
    public function voucher()
    {
        return $this->belongsTo(Voucher::class);
    }

    
   
}