<?php

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
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
        Schema::create('order_details', function (Blueprint $table) {
            $table->id(); // ID của order_detail
            $table->foreignIdFor(Product::class)->nullable()->constrained()->onDelete('set null'); // Liên kết với bảng products
            $table->foreignIdFor(ProductVariant::class)->nullable()->constrained()->onDelete('set null'); // Liên kết với bảng product_variants nếu có
            $table->foreignIdFor(Order::class)->constrained()->onDelete('cascade'); // Liên kết với bảng orders
            $table->string('product_name'); // Tên sản phẩm
            $table->string('product_img'); // Ảnh sản phẩm
            $table->json('attributes')->nullable(); // Các thuộc tính của sản phẩm nếu có
            $table->integer('quantity'); // Số lượng sản phẩm
            $table->decimal('price', 15, 2); // Giá sản phẩm
            $table->decimal('total_price', 15, 2); // Tổng giá trị của mục đơn hàng
            $table->decimal('discount', 15, 2)->default(0);  // Giảm giá của sản phẩm, nếu có
            $table->timestamps(); // Thời gian tạo và cập nhật
            // Thêm chỉ mục
            $table->index('product_id');
            $table->index('order_id');
            // $table->index('voucher_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_details');
    }
};