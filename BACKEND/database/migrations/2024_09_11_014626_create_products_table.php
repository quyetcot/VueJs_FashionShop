<?php

use App\Models\Brand;
use App\Models\Category;
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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Brand::class)->nullable()->constrained()->onDelete("set null");
            $table->foreignIdFor(Category::class)->nullable()->constrained()->onDelete("set null");
            $table->boolean('type')->default(false)->comment("loại sản phẩm 1-productvariant|0-simpleproduct");
            $table->string('slug');
            $table->string('sku');
            $table->decimal('weight')->comment('cân nặng tính bằng gam');
            $table->string('name')->unique();
            $table->integer("views")->default(0);
            $table->string('img_thumbnail');
            $table->double('price_regular')->nullable();
            $table->double('price_sale')->nullable();
            $table->integer("quantity")->nullable();
            $table->text('description');
            $table->text("description_title");
            $table->boolean("status")->default(true);
            $table->boolean('is_show_home')->default(true);
            $table->boolean('trend')->default(true);
            $table->boolean('is_new')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
