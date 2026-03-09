<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variant_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_history_id')->constrained()->cascadeOnDelete();
            // nullable: variant có thể đã bị xoá sau khi snapshot
            $table->foreignId('product_variant_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('sku')->nullable();
            $table->decimal('cost_price', 15, 2)->default(0);
            $table->timestamps();

            $table->index('product_history_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variant_histories');
    }
};
