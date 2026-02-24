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
        Schema::create('monthly_kol_costs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');  // 1-12
            $table->decimal('kol_cost', 15, 2)->default(0); // Chi phí KOL (nhập tay theo tháng)
            // monthly_profit = SUM(daily_profit) - kol_cost (computed)

            $table->timestamps();

            $table->unique(['shop_id', 'year', 'month']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monthly_kol_costs');
    }
};
