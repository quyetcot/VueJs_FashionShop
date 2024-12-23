<?php

use App\Models\Order;
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
        Schema::create('voucher_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Voucher::class)->onDelete('cascade'); // Liên kết đến bảng vouchers
            $table->foreignIdFor(User::class)->onDelete('cascade'); // Liên kết đến bảng users
            $table->foreignIdFor(Order::class)->onDelete('cascade'); // Liên kết đến bảng users
            $table->enum('action', ['used', 'reverted'])->default('used'); // hoạt động:used, reverted(nếu đơn hàng được trả lại)
            $table->timestamps(); // Thời gian tạo và cập nhật
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('voucher_logs');
    }
};
