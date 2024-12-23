<?php

use App\Models\Attribute;
use App\Models\Product;
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
        Schema::create('product_has_attributes', function (Blueprint $table) {
          $table->foreignIdFor(Product::class)->constrained()->cascadeOnDelete();
          $table->foreignIdFor(Attribute::class)->constrained()->cascadeOnDelete();
          $table->json('attribute_item_ids');
          $table->primary(["product_id","attribute_id"]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_has_attributes');
    }
};
