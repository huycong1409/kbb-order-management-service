<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('version');
            $table->string('name');
            $table->string('sku')->nullable();
            $table->decimal('cost_price', 15, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            // Khoảng thời gian phiên bản này có hiệu lực
            $table->timestamp('effective_from');
            $table->timestamp('effective_to')->nullable(); // null = phiên bản hiện tại

            $table->timestamps();

            $table->index(['product_id', 'effective_from']);
            $table->index(['product_id', 'effective_to']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_histories');
    }
};
