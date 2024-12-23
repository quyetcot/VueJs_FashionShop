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
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id(); // Khóa chính tự động, sử dụng kiểu bigint
            $table->string('name'); // Tên của phương thức thanh toán
            $table->text('description')->nullable(); // Mô tả chi tiết
            $table->enum('status', ['active', 'inactive'])->default('active'); // Trạng thái của phương thức thanh toán
            $table->timestamps(); // created_at và updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};