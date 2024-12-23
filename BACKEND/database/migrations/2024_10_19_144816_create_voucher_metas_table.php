<?php

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
        Schema::create('voucher_metas', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Voucher::class)->constrained()->onDelete('cascade'); // Liên kết đến bảng vouchers;
            $table->string('meta_key'); // Khóa meta (ví dụ: _voucher_category_ids)
            $table->text('meta_value'); // Giá trị của khóa meta (có thể là chuỗi hoặc JSON)
            $table->timestamps(); // Thời gian tạo và cập nhật
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('voucher_metas');
    }
};
