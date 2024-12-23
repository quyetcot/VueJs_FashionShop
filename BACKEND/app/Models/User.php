<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Notifications\CustomVerifyEmail;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        "role_id",
        'name',
        'email',
        'password',
        "phone_number",
        'address',
        'avatar',
        'birth_date',
        'gender',
        "is_active",
        "email_verified_at"
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function comments()
    {
        return $this->hasMany(Comments::class);
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }
    public function carts()
    {
        return $this->hasMany(Cart::class);
    }

    public function sendEmailVerificationNotification()
    {
        $this->notify(new CustomVerifyEmail());
    }
     // Liên kết đến voucher_logs
     public function voucherLogs()
     {
         return $this->hasMany(VoucherLog::class);
     }
    public function messages(){
        return $this->hasMany(Message::class);
    }
    public function conversations(){
        return $this->belongsToMany(Conversation::class,"conversation_users","user_id","conversation_id")->withPivot('is_deleted');
    }

    public function addresses()
{
    return $this->hasMany(Address::class);
}
}
