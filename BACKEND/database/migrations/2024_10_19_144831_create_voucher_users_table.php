<?php

use App\Models\User;
use App\Models\Voucher;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('voucher_users', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Voucher::class)->onDelete('cascade'); // Liên kết đến bảng vouchers
            $table->foreignIdFor(User::class)->onDelete('cascade'); // Liên kết đến bảng users
            $table->integer('usage_count')->default(0); // Số lần người dùng đã sử dụng voucher
            $table->timestamps(); // Thời gian tạo và cập nhật
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('voucher_users');
    }
};
