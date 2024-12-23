<?php

use App\Models\OrderDetail;
use App\Models\ReturnRequest;
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
        Schema::create('return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(ReturnRequest::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(OrderDetail::class)->constrained()->cascadeOnDelete();
            $table->integer('quantity');
            $table->text('image')->nullable();
            $table->decimal('refund_amount', 10, 2)->default(0);

            $table->enum('status', ['pending', 'canceled', 'approved', 'rejected'])->default('pending');


            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('return_items');
    }
};
