<?php

namespace App\Models;

use App\Models\VoucherUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Voucher extends Model
{
    use HasFactory;
    protected $fillable = [
        'title',
        'description',
        'code',
        'discount_type',
        'discount_value',
        'start_date',
        'end_date',
        'min_order_value',
        'usage_limit',
        'used_count',
        'is_active',
    ];

    // Liên kết đến bảng voucher_meta
    public function meta()
    {
        return $this->hasMany(VoucherMeta::class);
    }

    // Liên kết đến bảng voucher_uses
    public function users()
    {
        return $this->hasMany(VoucherUser::class);
    }

    // Kiểm tra số lần sử dụng còn lại
    public function remainingUsage()
    {
        $totalUses = $this->uses()->sum('times_used');
        return $this->usage_limit - $totalUses;
    }
    // Liên kết đến voucher_logs
    public function logs()
    {
        return $this->hasMany(VoucherLog::class);
    }

    public function checkUsageLimit()
    {
        if ($this->usage_count >= $this->usage_limit) {
            $this->is_active = false;
            $this->save();
        }
    }

}
