<?php

use App\Models\Attribute;
use App\Models\AttributeItem;
use App\Models\ProductVariant;
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
        Schema::create('product_variant_has_attributes', function (Blueprint $table) {
           $table->foreignIdFor(ProductVariant::class)->constrained()->cascadeOnDelete();
           $table->foreignIdFor(Attribute::class)->constrained()->cascadeOnDelete();
           $table->foreignIdFor(AttributeItem::class)->constrained()->cascadeOnDelete();
           $table->primary(["product_variant_id","attribute_id","attribute_item_id"]);
           $table->string('value');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_variant_has_attributes');
    }
};
