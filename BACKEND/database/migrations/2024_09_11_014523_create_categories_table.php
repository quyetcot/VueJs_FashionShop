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
        Schema::create('categories', function (Blueprint $table) {
            $table->id(); // Tạo khóa chính 'id'
            $table->string('slug')->unique(); // Tạo trường slug và đảm bảo duy nhất
            $table->string('name'); // Tên danh mục
            $table->text('description')->nullable(); // Mô tả, có thể nullable
            $table->string('img_thumbnail')->nullable(); // Đường dẫn ảnh thumbnail, nullable
            $table->foreignId('parent_id')->nullable()->constrained('categories')->onDelete('set null'); // Tham chiếu đến bảng categories
            $table->timestamps(); // Tạo trường created_at và updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
