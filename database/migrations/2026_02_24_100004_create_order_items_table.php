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
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained()->nullOnDelete();

            // Snapshot tại thời điểm import (không đổi dù product thay đổi sau)
            $table->string('product_name');           // Tên sản phẩm
            $table->string('variant_name')->nullable(); // Tên phân loại hàng
            $table->string('product_sku')->nullable();
            $table->string('variant_sku')->nullable();

            $table->unsignedInteger('quantity')->default(1);         // Số lượng
            $table->decimal('cost_price', 15, 2)->default(0);        // Giá vốn (snapshot)
            $table->decimal('original_price', 15, 2)->default(0);    // Giá gốc
            $table->decimal('sale_price', 15, 2)->default(0);        // Giá ưu đãi
            $table->decimal('selling_price', 15, 2)->default(0);     // Tổng giá bán (sản phẩm)

            // Thuế & Tổng vốn tính theo dòng
            // tax          = selling_price * 0.015
            // total_cost   = quantity * cost_price
            // profit       = selling_price - (shared_fees_per_item + tax + total_cost)
            // (shared_fees phân bổ từ order level)

            $table->timestamps();

            $table->index(['order_id']);
            $table->index(['product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
