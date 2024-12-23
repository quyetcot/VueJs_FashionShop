<?php

use App\Models\User;
use App\Models\Order;
use App\Models\Voucher;
use App\Models\PaymentMethod;
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
        Schema::create('orders', function (Blueprint $table) {
            $table->id(); // ID đơn hàng
            $table->foreignIdFor(User::class)->nullable()->constrained()->onDelete('set null'); // Liên kết với bảng users
            $table->foreignIdFor(PaymentMethod::class)->constrained()->onDelete('cascade'); // Liên kết với bảng payment_methods
            $table->enum('order_status', \App\Models\Order::getOrderStatuses())->default(\App\Models\Order::STATUS_PENDING); // Trạng thái đơn hàng (completed, pending, shipped, v.v.).
            $table->enum('payment_status', \App\Models\Order::getPaymentStatuses())->default(\App\Models\Order::PAYMENT_PENDING); // Trạng thái thanh toán
            $table->string('order_code')->unique(); // Mã đơn hàng
            $table->integer('total_quantity'); // Tổng số lượng
            $table->decimal('total', 15, 2); // Tổng giá trị đơn hàng
            $table->string('user_name'); // Tên người đặt hàng
            $table->string('user_email'); // Email người đặt hàng
            $table->string('user_phonenumber'); // Số điện thoại người đặt hàng
            $table->text('user_address'); // Địa chỉ người đặt hàng
            $table->text('user_note')->nullable(); // Ghi chú của người đặt hàng
            $table->string('ship_user_name')->nullable(); // Tên người nhận
            $table->string('ship_user_phonenumber')->nullable(); // Số điện thoại người nhận
            $table->text('ship_user_address')->nullable(); // Địa chỉ người nhận
            $table->string('shipping_method')->nullable(); // Phương thức vận chuyển
            $table->decimal('shipping_fee', 15, 2)->default(0);  // Trường phí ship
            $table->foreignIdFor(Voucher::class)->nullable()->constrained()->onDelete('set null'); // Thêm khóa ngoại với cột voucher_id cho phép null
            $table->decimal('voucher_discount', 15, 2)->default(0);  // Số tiền giảm giá từ voucher (nếu có)
            $table->text('return_notes')->nullable();
            $table->timestamps(); // Thời gian tạo và cập nhật

            // Thêm chỉ mục
            $table->index('user_id');
            $table->index('payment_method_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
