<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('title'); // Tên của voucher
            $table->string('description')->nullable(); // Mô tả voucher
            $table->string('code')->unique(); // Mã voucher (duy nhất)
            $table->enum('discount_type', ['percent', 'fixed']); // Loại giảm giá (percent = phần trăm, fixed = cố định)
            $table->decimal('discount_value', 10, 2); // Số tiền giảm giá (hoặc phần trăm)
            $table->timestamp('start_date')->nullable(); // Ngày bắt đầu voucher
            $table->timestamp('end_date')->nullable(); // Ngày kết thúc voucher
            $table->decimal('min_order_value', 10, 2); // Số tiền giảm giá (hoặc phần trăm)
            $table->integer('usage_limit')->nullable(); // Số lần sử dụng tối đa của voucher
            $table->integer('used_count')->nullable(); 
            $table->boolean('is_active')->default(true); // Chỉ áp dụng cho khách hàng mới hay không
            $table->timestamps(); // Thời gian tạo và cập nhật
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vouchers');
    }
};
