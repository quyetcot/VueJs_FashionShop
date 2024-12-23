<?php

use App\Models\Attribute;
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
        Schema::create('attribute_items', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Attribute::class)->constrained()->onDelete('cascade');
            $table->string('value')->unique();
            $table->string('slug');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attribute_items');
    }
};
